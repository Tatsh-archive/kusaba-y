<?php
class ThreadController extends MoorActionController {
  /**
   * @var Thread
   */
  private $thread;
  
  public function beforeAction() {
    try {
      $this->thread = new Thread(fRequest::get('id', 'integer'));
    }
    catch (fNotFoundException $e) {
      fURL::redirect('/not-found/');
    }
  }

  public function index() {
    $title = $this->thread->getTitle();
    
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
    $form->addAction('submit', __('Submit'));

    $form_html = $form->make();
    $content = sTemplate::buffer('board-header', array(
      'boards' => fRecordSet::build('Board', array(), array('name' => 'ASC')),
    ));
    $content .= sTemplate::buffer('thread-header', array(
      'reply_form' => $form_html,
      'board_url' => '/'.$this->thread->createBoard()->getShortURL().'/',
    ));

    $content .= sTemplate::buffer('thread-list', array(
      'thread' => $this->thread,
      'replies' => fRecordSet::build('Reply', array('thread_id=' => $this->thread->getId()), array('date_updated' => 'ASC')),
    ));
    
    sTemplate::render(array('content' => $content, 'title' => $title));
  }
}
