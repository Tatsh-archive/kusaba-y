<?php
class kCore extends sCore {
  private static $settings = NULL;
  
  public static function getCache() {
    if (self::$cache === NULL) {
      self::$cache = new sCache('apc');
    }
    return self::$cache;
  }

  public static function getDatabase() {
    if (self::$db === NULL) {
      $ini = parse_ini_file('./config/database.ini', TRUE);

      if ($ini === FALSE || !isset($ini['database'])) {
        throw new fUnexpectedException('INI file not found or is invalid.');
      }
      
      $ini = $ini['database'];
      $required_keys = array('type', 'name', 'user', 'password', 'host');

      foreach ($required_keys as $key) {
        if (!isset($ini[$key]) || !$ini[$key]) {
          throw new fUnexpectedException('INI file section "%s" is missing key "%s" or key "%s" has an invalid value', 'database', $key, $key);
        }
      }

      self::$db = new fDatabase($ini['type'],
        $ini['name'],
        $ini['user'],
        $ini['password'],
        $ini['host'],
        isset($ini['port']) ? (int)$ini['port'] : NULL,
        isset($ini['timeout']) ? (int)$ini['timeout'] : NULL
      );
      fORMDatabase::attach(self::$db);
    }

    return self::$db;
  }

  private static function cast($value, $type = NULL) {
    switch ($type) {
      case 'int':
      case 'integer':
        $value = (int)$value;
        break;

      case 'float':
        if (is_object($value)) {
          break;
        }
        $value = (float)$value;
        break;

      case 'bool':
      case 'boolean':
        $value = (bool)$value;
        break;
    }

    return $value;
  }

  public static function getSetting($name, $cast_to = NULL, $default_value = NULL) {
    if (self::$settings === NULL) {
      $set = fRecordSet::build('SiteSetting');
      self::$settings = array();
      
      foreach ($set as $item) {
        self::$settings[$item->getName()] = $item->getValue();
      }
    }

    if (isset(self::$settings[$name])) {
      return self::cast(self::$settings[$name], $cast_to);
    }

    return $default_value;
  }

  public static function setSetting($name, $value) {
    try {
      $setting = new SiteSetting($name);
    }
    catch (fNotFoundException $e) {
      $setting = new SiteSetting;
      $setting->setName($name);
      $setting->setValue($value);
    }

    $setting->store();
    self::$settings[$name] = $value;
  }

  protected static function configureAuthorization() {
    sAuthorization::setAuthLevels(self::getSetting('auth.levels', 'array', array(
      'admin' => 100,
      'moderator' => 50,
      'guest' => 1,
    )));
    sAuthorization::setLoginPage('/login/');
  }

  public static function configureTemplate() {
    sTemplate::setCache(self::getCache());
    sTemplate::setActiveTemplate(self::getSetting('template.name','string', 'kusaba-default'));
    sTemplate::setSiteName(self::getSetting('site.name', 'string', __('{Kusaba-Y}: No site name')));
    sTemplate::setSiteSlogan(self::getSetting('site.slogan', 'string', ''));
  }

  public static function main() {
    parent::main();
    self::configureTemplate();
    kRouter::run();
  }
}
