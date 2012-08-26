<?php
class BoardController extends MoorActionController {
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

  public function makeBoardPost() {
    try {
      $validation = new fValidation;
      $storage_dir = new fDirectory(kCore::getSetting(
        'files.image_upload_directory',
        'string',
        './files/images'
      ));
      $purifier = kCore::getHTMLPurifier();
      $html = $purifier->purify(fRequest::get('message'));
      $date = new fDate(kCore::getSetting('posts.expiration_time', 'string', '+1 week'));

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
      $uploader->setMIMETypes(
        $this->default_image_types,
        fText::compose('The file uploaded is not an image or is not an allowed type: %s',
          join(', ', $this->default_image_types)
        )
      );
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
