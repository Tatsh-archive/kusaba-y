<?php
class Reply extends fActiveRecord {
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'date_updated', 'timezone');
  }

  public function getId() {
    return $this->getReplyId();
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
}
