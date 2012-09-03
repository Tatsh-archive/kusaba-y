<?php
class ImageFile extends fActiveRecord {
  const makeUniqueId = 'ImageFile::makeUniqueId';

  /**
   * @todo Move file upload column path to configuration.
   */
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'date_updated', 'timezone');

    fORMFile::configureImageUploadColumn($this, 'filename', './files/images');
    
    fORMFile::configureImageUploadColumn($this, 'filename_thumb', './files/images/thumbs');
    fORMFile::addFImageMethodCall($this, 'filename_thumb', 'resize', array(250, NULL));
    fORMFile::configureColumnInheritance($this, 'filename_thumb', 'filename');

    fORM::registerHookCallback($this, 'pre::validate()', self::makeUniqueId);
  }

  public function getId() {
    return $this->getFileId();
  }

  public function encodeId() {
    return $this->encodeFileId();
  }

  /**
   * @internal
   *
   * @param ImageFile $object
   * @param array $values
   * @param array $old_values
   */
  public static function makeUniqueId($object, &$values, &$old_values) {
    if (fActiveRecord::hasOld($old_values, 'unique_id')) {
      return;
    }

    $records = fRecordSet::build(__CLASS__);
    $ids = array();
    $id = fCryptography::randomString(16, 'numeric');

    foreach ($records as $record) {
      $ids[] = $record->getUniqueId();
    }

    while (in_array($id, $ids)) {
      $id = fCryptography::randomString(16, 'numeric');
    }

    fActiveRecord::assign($values, $old_values, 'unique_id', $id);
  }
}
