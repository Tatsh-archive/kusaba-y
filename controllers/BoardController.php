<?php
class BoardController extends MoorActionController {
  const POST_KEY = 'BoardController::POST_KEY';
  
  private $board;

  /**
   * @todo Move accepted image types to configuration.
   */
  private $default_image_types = array('image/jpeg', 'image/gif', 'image/png');

  private $default_max_size = '10MB';
  
  public function beforeAction() {
    $this->board = $this->getBoardByShortURL();
  }

  public function getBoardByShortURL() {
    try {
      $url = substr(fURL::get(), 1, -1);
      $set = fRecordSet::build('Board', array('short_u_r_l=' => $url));
      $set->tossIfEmpty();
      return $set[0];
    }
    catch (fEmptySetException $e) {
      fURL::redirect('/not-found/');
    }
  }

  /**
   * @todo Move initialisation of HTMLPurifier and HTMLPurifier_Config to
   *   elsewhere. Make configurable via file.
   */
  public function makeBoardPost() {
    try {
      $validation = new fValidation;
      $storage_dir = new fDirectory('./files/images');
      $config = HTMLPurifier_Config::createDefault();
      
      $config->set('Cache.SerializerPath', './files');
      $config->set('AutoFormat.AutoParagraph', TRUE);
      $date = new fDate('+1 day');
      
      $purifier = new HTMLPurifier($config);
      $html = $purifier->purify(fRequest::get('message'));

      if (!$html) {
        fRequest::set('message', '');
      }

      $validation->addRequiredFields('title', 'message', 'deletion_password');
      $validation->setCSRFTokenField('csrf');
      $validation->addEmailFields('email_address');
      $validation->validate();

      $thread = new Thread;
      $thread->populate();
      $thread->setMessage($html);
      $thread->setBoardName($this->board->getName());
      $thread->setExpirationTime($date);

      if (strtolower($thread->getName()) !== 'anonymous') {
        $thread->setIsAnonymous(FALSE);
      }

      $uploader = new fUpload;
      $uploader->setMIMETypes($this->default_image_types, 'The file uploaded is not an image');
      $uploader->setMaxSize($this->default_max_size);
      $uploader->setOptional(TRUE);
      $file = $uploader->move($storage_dir, 'image_files::filename');

      if ($file !== NULL) {
        $image = new ImageFile;
        $image->setFilename($file);
        $image->store();
        $thread->setImageFileId($image->getId());
      }
      else {
        $thread->setImageFileId(ImageFile::getDefaultId());
      }
      
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

  public function index() {
    if (fRequest::isPost()) {
      return $this->makeBoardPost();
    }

    $title = $this->board->getName();
    $page = fRequest::get('page', 'integer', 1);
    $limit = 15;
    $boards = fRecordSet::build('Board', array(), array('short_u_r_l' => 'ASC'));

    sRequest::setPostValues(self::POST_KEY);
    
    $form = new sCRUDForm('Thread');
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
    $form->addField('image_files::filename', __('Image'), 'file', array('accept' => join(',', $this->default_image_types)));
    $form->addAction('submit', __('Submit'));

    $threads = fRecordSet::build('Thread', array(
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
      'threads' => $threads,
      'last_validation_message' => fMessaging::retrieve('validation', fURL::get()),
      'board_form' => $form->make(),
      'pagination' => $pagination->makeLinks(),
    ));
    
    sTemplate::render(array('title' => $title, 'content' => $content));
  }
}
