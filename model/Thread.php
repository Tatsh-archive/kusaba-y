<?php
class Thread extends fActiveRecord {
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'date_updated', 'timezone');
  }

  public function getId() {
    return $this->getThreadId();
  }

  public function encodeId() {
    return $this->encodeThreadId();
  }

  public function hasValidImageFile() {
    return $this->createImageFile()->getFilename() instanceof fImage;
  }

  public function encodeReplyURL() {
    return '/'.$this->createBoard()->encodeShortURL().'/res/'.$this->encodeId();
  }
}
