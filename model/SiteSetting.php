<?php
class SiteSetting extends fActiveRecord {
  protected function configure() {
    fORMDate::configureDateCreatedColumn($this, 'date_created');
    fORMDate::configureDateUpdatedColumn($this, 'date_updated');
    fORMDate::configureTimezoneColumn($this, 'date_updated', 'timezone');
    sORMJSON::configureJSONSerializedColumn($this, 'setting_value');
  }

  public function getValue() {
    return $this->getSettingValue();
  }

  public function setValue($value) {
    return $this->setSettingValue($value);
  }
}
