<?php
class BoardController extends MoorActionController {
  const validateRecaptchaResponse = 'BoardController::validateRecaptchaResponse';

  const POST_KEY = 'BoardController::POST_KEY';

  private $board;

  private $default_image_types;

  private $default_max_size;

  public function beforeAction() {
    $this->board = $this->getBoardByShortURL();
    $this->default_image_types = kCore::getSetting('posts.allowed_image_types', 'array', array(
      'image/jpeg',
      'image/gif',
      'image/png'
    ));
    $this->default_max_size = kCore::getSetting('posts.image_maximum_file_size', 'string', '10MB');
  }

  private function getBoardByShortURL() {
    try {
      $url = fURL::get();

      if (substr($url, 0, 7) === '/admin/') {
        fAuthorization::requireAuthLevel('admin');
        return;
      }

      $url = substr($url, 1, -1);
      $set = fRecordSet::build(Board, array('short_u_r_l=' => $url));
      $set->tossIfEmpty();

      return $set[0];
    }
    catch (fEmptySetException $e) {
      fURL::redirect('/not-found/');
    }
  }

  /**
   * @todo
   */
  public function adminPost() {
    fURL::redirect();
  }


  public function admin() {
    if (fRequest::isPost()) {
      return $this->adminPost();
    }

    $title = __('Board Management');
    $sort_column = fCRUD::getSortColumn(array('name', 'short_u_r_l'));
    $sort = fCRUD::getSortDirection('asc');
    fCRUD::redirectWithLoadedValues();

    $nav = AdminController::getLinksHTML();
    $header = sTemplate::buffer('header-h1', array('text' => $title));
    $boards = fRecordSet::build('Board', array(), array($sort_column => $sort));
    $content = sTemplate::buffer('admin-boards-list', array('boards' => $boards));

    sTemplate::render(array('content' => $header.$nav.$content, 'title' => $title));
  }

  /**
   * @todo
   */
  public function adminEditBoardPost() {
    fURL::redirect();
  }

  /**
   * @note For some reason the name field does not get created here. Some
   *   logic may be causing it to be overwritten by the category_name field in
   *   sCRUDForm.
   */
  public function adminEditBoard() {
    try {
      if (fRequest::isPost()) {
        return $this->adminEditBoardPost();
      }

      $url = substr(fURL::get(), 14, -1);
      $board = new Board(array('short_u_r_l' => $url));
      $form = new sCRUDForm($board);

      $form->addField('name', __('Name'), 'textfield', array('required' => TRUE));
      $form->enableCSRFField(TRUE);
      $form->overrideLabel('short_u_r_l', __('Short URL'));
      $form->hideFields('date_updated', 'date_created', 'timezone');
      $form->setFieldOrder(array('name', 'category_name'));
      $form->addAction('save', 'Save');
      $form->addAction('continue', __('Save and Continue Editing'));
      $form->addAction('delete', __('Delete'));

      $content = $form->make();

      sTemplate::render(array('content' => $content, 'title' => __('Edit "!board"', array('!board' => $board->getName()))));
    }
    catch (fNotFoundException $e) {
      fURL::redirect('/not-found/');
    }
  }

  /**
   * @return Thread|Reply
   */
  private static function getThreadOrReply($id) {
    try {
      return new Thread($id);
    }
    catch (fNotFoundException $e) {
      return new Reply($id);
    }
  }

  /**
   * @param string $message
   * @return string
   */
  public static function fixIdReferences($message) {
    $lines = explode("\n", $message);

    foreach ($lines as $key => $line) {
      $line = trim($line);
      $gt = substr($line, 0, 2);
      $id = substr($line, 2);

      if ($gt === '>>' && is_numeric($id)) {
        try {
          $object = self::getThreadOrReply($id);
          $type = $object instanceof Thread ? 'thread' : 'reply';
          $lines[$key] = '>>'.sHTML::tag('a', array('href' => '#', 'data-'.$type.'-id' => $id), $id).'<br>';
        }
        catch (fNotFoundException $e) {}
      }
    }

    return join("\n", $lines);
  }

  /**
   * @internal
   */
  public static function validateRecaptchaResponse($response) {
    try {
      self::configureRecaptcha();
      $response = recaptcha::checkAnswer($_SERVER['REMOTE_ADDR'], fRequest::get('recaptcha_challenge_field'), $response);
      return isset($response['is_valid']) ? $response['is_valid'] === TRUE : FALSE;
    }
    catch (recaptchaException $e) {}
    return FALSE;
  }

  public function makeBoardPost() {
    try {
      $validation = new fValidation;
      $storage_dir = new fDirectory(kCore::getSetting(
        'files.image_upload_directory',
        'string',
        './files/images'
      ));
      $message = self::fixIdReferences(fRequest::get('message'));
      $html = kHTML::prepare($message);
      $date = new fDate(kCore::getSetting('posts.expiration_time', 'string', '+1 week'));

      if (!$html) {
        fRequest::set('message', '');
      }

      $validation->addRequiredFields('title', 'message', 'deletion_password');
      $validation->setCSRFTokenField('csrf');
      $validation->addEmailFields('email_address');
      $validation->addCallbackRule('recaptcha_response_field', __CLASS__.'::validateRecaptchaResponse', __('The value entered does not match the text'));
      $validation->validate();

      $image_id = NULL;
      $thread = new Thread;
      $thread->populate();
      $thread->setMessage($html);
      $thread->setBoardName($this->board->getName());
      $thread->setExpirationTime($date);

      if (strtolower($thread->getName()) !== 'anonymous') {
        $thread->setIsAnonymous(FALSE);
      }

      try {
        $image = $thread->createImageFile();
        $fimage = $image->uploadFilename();

        if ($fimage instanceof fImage) {
          $image->setOriginalFilename($fimage->getName());
          $image->validate();

          $mime = $fimage->getMimeType();
          $extension = 'jpeg';
          switch ($mime) {
            case 'image/png':
              $extension = 'png';
              break;

            case 'image/gif':
              $extension = 'gif';
              break;
          }

          $fimage->rename($image->getUniqueId().'.'.$extension, FALSE);
          $image->store();
          $image_id = $image->getId();
        }
      }
      catch (fNotFoundException $e) {
        fCore::debug(sprintf('Caught not found exception with message: "%s"', strip_tags($e->getMessage())));
      }

      $thread->setImageFileId($image_id);
      $thread->store();

      sRequest::deletePostValues(self::POST_KEY);
      fURL::redirect();
    }
    catch (fValidationException $e) {
      fMessaging::create('validation', fURL::get(), $e->getMessage());
      sRequest::savePostValues(self::POST_KEY);
      fURL::redirect();
    }
  }

  public static function configureRecaptcha() {
    $captcha = kYAML::decodeFile('./config/recaptcha.yml');
    recaptcha::setPrivateKey($captcha['private_key']);
    recaptcha::setPublicKey($captcha['public_key']);
    recaptcha::registerComposeCallback('__');
  }

  public function index() {
    if (fRequest::isPost()) {
      return $this->makeBoardPost();
    }

    $title = $this->board->getName();
    $page = fRequest::get('page', 'integer', 1);
    $limit = 15;
    $boards = fRecordSet::build(Board, array(), array('short_u_r_l' => 'ASC'));

    sRequest::setPostValues(self::POST_KEY);
    self::configureRecaptcha();

    $form = new sCRUDForm(Thread);
    $form->hideFields(array(
      'board_name',
      'is_anonymous',
      'file_id',
      'image_file_id',
      'expiration_time',
      'date_created',
      'date_updated',
      'timezone',
    ));
    $form->enableCSRFField(TRUE);
    $form->setFieldAttributes('title', array('autocomplete' => 'off'));
    $form->setFieldAttributes('deletion_password', array('autocomplete' => 'off', 'value' => fCryptography::randomString(16)));
    $form->addField('filename', __('Image'), 'file', array('accept' => join(',', $this->default_image_types)));
    $form->addField('verification', '', 'hidden');
    $form->replaceHTML('verification', join('', array(
      '<div class="form-field-container">',
        '<label class="form-label">'.fHTML::encode(__('Verification')).'</label>',
        recaptcha::getHTML(),
      '</div>',
    )));
    $form->addAction('submit', __('Submit'));

    $threads = fRecordSet::build(Thread, array(
        'board_name=' => $title,
      ),
      array('date_updated' => 'DESC'),
      $limit,
      $page
    );
    $pagination = new fPagination($threads, $limit, $page);
    $content = sTemplate::buffer('board-header', array(
      'boards' => $boards,
    ));
    $content .= sTemplate::buffer('board-list', array(
      'title' => $title,
      'threads' => $threads,
      'last_validation_message' => fMessaging::retrieve('validation', fURL::get()),
      'board_form' => $form->make(),
      'pagination' => $threads->makeLinks(),
      'loading_img_src' => kCore::getSetting('posts.loading_image_source', 'string', '/files/images/loading.png'),
    ));

    sTemplate::render(array('title' => $title, 'content' => $content));
  }
}
