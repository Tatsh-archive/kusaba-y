<?php
class kCore extends sCore {
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
  
  public static function getSetting($name, $default_value) {
  }

  protected static function configureAuthorization() {
    sAuthorization::setAuthLevels(array('admin' => 100, 'user' => 50, 'guest' => 25));
    sAuthorization::setLoginPage('/login');
  }

  public static function main() {
    parent::main();
  }
}
