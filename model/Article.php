<?php
class Article extends fActiveRecord {
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'timezone');
  }

  public function getId() {
    return $this->getArticleId();
  }
}
