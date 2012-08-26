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
    $image = $this->createImageFile();

    if (!($image instanceof ImageFile)) {
      return FALSE;
    }

    if ($image->getFilename() === ImageFile::NO_IMAGE_VALUE) {
      return FALSE;
    }

    return TRUE;
  }

  public function encodeReplyURL() {
    return '/'.$this->createBoard()->encodeShortURL().'/res/'.$this->encodeId();
  }
}
