<?php
class kCore extends sCore {
  const debugCallback            = 'kCore::debugCallback';
  const exceptionClosingCallback = 'kCore::exceptionClosingCallback';
  const getCache                 = 'kCore::getCache';
  const getDatabase              = 'kCore::getDatabase';
  const getSetting               = 'kCore::getSetting';
  const isProductionMode         = 'kCore::isProductionMode';
  
  /**
   * @var array
   */
  private static $settings = NULL;

  /**
   * @var HTMLPurifier
   */
  private static $purifier = NULL;

  /**
   * @return sCache
   */
  public static function getCache() {
    if (self::$cache === NULL) {
      self::$cache = new sCache('apc');
    }
    return self::$cache;
  }

  /**
   * @return fDatabase
   */
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

  /**
   * @return mixed
   */
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

  /**
   * @return mixed
   */
  public static function getSetting($name, $cast_to = NULL, $default_value = NULL) {
    $cache = self::getCache();
    
    if (self::$settings === NULL) {
      self::$settings = $cache->get('settings');
    }

    if (!is_array(self::$settings)) {
      $set = fRecordSet::build('SiteSetting');
      self::$settings = array();

      foreach ($set as $item) {
        self::$settings[$item->getName()] = $item->getValue();
      }

      $cache->set('settings', self::$settings, 7200);
    }

    if (isset(self::$settings[$name])) {
      return self::cast(self::$settings[$name], $cast_to);
    }

    return $default_value;
  }

  /**
   * @return HTMLPurifier
   */
  public static function getHTMLPurifier() {
    if (self::$purifier === NULL) {
      $config = HTMLPurifier_Config::createDefault();
      $config->set('Cache.SerializerPath', self::getSetting('htmlpurifier.serializer_path', 'string', './files'));
      $config->set('AutoFormat.AutoParagraph', TRUE);
      self::$purifier = new HTMLPurifier($config);
    }

    return self::$purifier;
  }

  /**
   * @return boolean
   */
  public static function isProductionMode() {
    return self::getSetting('site.mode', 'boolean', FALSE);
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

  /**
   * Debug callback.
   *
   * @internal
   *
   * @param string $message Message to log.
   * @return void
   */
  public static function debugCallback($message) {
    $log_path = self::getSetting('site.error-log-destination', 'string', '/var/log/sutra/kusaba-y.log');

    if (is_file($log_path)) {
      file_put_contents($log_path, $message."\n", LOCK_EX | FILE_APPEND);
    }
  }

  /**
   * @internal
   */
  public static function exceptionClosingCallback() {
    sResponse::sendServerErrorHeader();
    fJSON::sendHeader();
    print fJSON::encode('Unknown error occurred while generating this page.');
  }

  public static function configureTemplate() {
    sTemplate::setCache(self::getCache());
    sTemplate::setActiveTemplate(self::getSetting('template.name','string', 'kusaba-default'));
    sTemplate::setSiteName(self::getSetting('site.name', 'string', __('{Kusaba-Y}: No site name')));
    sTemplate::setSiteSlogan(self::getSetting('site.slogan', 'string', ''));
    sTemplate::setMode(self::isProductionMode() ? 'production' : 'development');
  }

  public static function main() {
    parent::main();
    
    $log_path = self::getSetting('site.error-log-destination', 'string', '/var/log/sutra/kusaba-y.log');

    self::enableErrorHandling($log_path);
    self::enableExceptionHandling($log_path, self::exceptionClosingCallback);
    self::registerDebugCallback(self::debugCallback);
    self::enableDebugging(!self::isProductionMode());
    self::configureTemplate();
    
    kRouter::run();
  }
}
