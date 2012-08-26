<?php
class ImageFile extends fActiveRecord {
  const NO_IMAGE_VALUE = '###none';

  /**
   * @todo Move file upload column path to configuration.
   */
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'date_updated', 'timezone');

    fORMFile::configureImageUploadColumn($this, 'filename', './files/images');
    
    fORMFile::configureImageUploadColumn($this, 'filename_thumb', './files/images/thumbs');
    fORMFile::addFImageMethodCall($this, 'filename_thumb', 'resize', array(250, 250));
    fORMFile::configureColumnInheritance($this, 'filename_thumb', 'filename');
  }

  public function getId() {
    return $this->getFileId();
  }

  public static function getDefaultId() {
    $set = fRecordSet::build(__CLASS__, array('filename=' => self::NO_IMAGE_VALUE), array(), 1, 1);
    return $set[0]->getId();
  }
}
