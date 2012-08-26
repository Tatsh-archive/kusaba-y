<?php
class ThreadController extends MoorActionController {
  const POST_KEY = 'ThreadController::POST_KEY';
  
  /**
   * @var Thread
   */
  private $thread;

  private $default_image_types;

  private $default_max_size;
  
  public function beforeAction() {
    try {
      $this->thread = new Thread(fRequest::get('id', 'integer'));
      $this->default_image_types = kCore::getSetting('posts.allowed_image_types', 'array', array(
        'image/jpeg',
        'image/gif',
        'image/png'
      ));
      $this->default_max_size = kCore::getSetting('posts.image_maximum_file_size', 'string', '10MB');
    }
    catch (fNotFoundException $e) {
      fURL::redirect('/not-found/');
    }
  }

  private function makeReplyPost() {
    try {
      $validation = new fValidation;
      $storage_dir = new fDirectory('./files/images');
      $date = new fDate('+1 day');
      $purifier = kCore::getHTMLPurifier();
      $html = $purifier->purify(fRequest::get('message'));

      if (!$html) {
        fRequest::set('message', '');
      }

      $validation->addRequiredFields('title', 'message', 'deletion_password');
      $validation->setCSRFTokenField('csrf');
      $validation->addEmailFields('email_address');
      $validation->validate();

      $thread = new Reply;
      $thread->populate();
      $thread->setMessage($html);
      $thread->setThreadId($this->thread->getId());

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
      return $this->makeReplyPost();
    }
    
    $title = $this->thread->getTitle();

    sRequest::setPostValues(self::POST_KEY);

    $limit = fRequest::getValid('limit', array(15, 25, 50)) - 1;
    $page = fRequest::get('page', 'integer', 1);
    $form = new sCRUDForm('Reply');
    
    $form->hideFields(array(
      'thread_id',
      'is_anonymous',
      'file_id',
      'image_file_id',
      'date_created',
      'date_updated',
      'timezone',
    ));
    $form->setFieldAttributes('title', array('autocomplete' => 'off'));
    $form->setFieldAttributes('deletion_password', array('autocomplete' => 'off', 'value' => fCryptography::randomString(16)));
    $form->addField('image_files::filename', __('Image'), 'file', array('accept' => join(',', $this->default_image_types)));
    $form->enableCSRFField(TRUE);
    $form->addAction('submit', __('Submit'));

    $form_html = $form->make();
    $replies = fRecordSet::build('Reply', array('thread_id=' => $this->thread->getId()), array('date_updated' => 'ASC'), $limit, $page);
    $pagination = new fPagination($replies, $limit, $page);
    $content = sTemplate::buffer('board-header', array(
      'boards' => fRecordSet::build('Board', array(), array('short_u_r_l' => 'ASC')),
    ));
    $content .= sTemplate::buffer('thread-header', array(
      'reply_form' => $form_html,
      'board_url' => '/'.$this->thread->createBoard()->getShortURL().'/',
      'last_validation_message' => fMessaging::retrieve('validation', fURL::get()),
    ));

    $content .= sTemplate::buffer('thread-list', array(
      'thread' => $this->thread,
      'replies' => $replies,
      'pagination' => $pagination->makeLinks(),
      'loading_img_src' => kCore::getSetting('posts.loading_image_source', 'string', '/files/images/loading.png'),
    ));
    
    sTemplate::render(array('content' => $content, 'title' => $title));
  }
}
