<?php
class kCore extends sCore {
  const debugCallback            = 'kCore::debugCallback';
  const exceptionClosingCallback = 'kCore::exceptionClosingCallback';
  const getCache                 = 'kCore::getCache';
  const getDatabase              = 'kCore::getDatabase';
  const getSetting               = 'kCore::getSetting';
  const isProductionMode         = 'kCore::isProductionMode';
  const closeLogHandle           = 'kCore::closeLogHandle';
  
  /**
   * @var array
   */
  private static $settings = NULL;

  /**
   * @var string
   */
  private static $log_path = NULL;

  /**
   * @var resource
   */
  private static $log_file_handle = NULL;

  /**
   * @var boolean
   */
  private static $log_file_exists = FALSE;

  /**
   * @var array
   *
   * @todo Move to configuration file.
   */
  private static $js_files = array(
    'jquery-cdn-fallback.js',
    'jquery.appear-r15.js',
    'ky.load-images.js',
  );

  /**
   * @var array
   */
  private static $css_files = array();

  /**
   * @var array
   *
   * @todo Move to configuration file.
   */
  private static $minified_js_files = array(
    'jquery-cdn-fallback.min.js',
  );

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
      $ini = kYAML::decodeFile('./config/database.yml');

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

      case 'string':
        $value = (string)$value;
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

    if (array_key_exists($name, self::$settings)) {
      return self::cast(self::$settings[$name], $cast_to);
    }
    else {
      try {
        $setting = new SiteSetting;
        $setting->setName($name);
        $setting->setValue($default_value);
        $setting->setLastEditedUserId(1); // TODO Need better way to get an admin user here
        $setting->store();
        self::$settings = NULL;
        $cache->delete('settings');
      }
      catch (fValidationException $e) {}
    }

    return $default_value;
  }

  /**
   * @return boolean
   */
  public static function isProductionMode() {
    return self::getSetting('site.production_mode', 'boolean', FALSE);
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
    if (self::$log_file_handle !== NULL) {
      fwrite(self::$log_file_handle, $message."\n");
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
    self::$css_files = kYAML::decodeFile('./config/css.yml');

    sTemplate::setCache(self::getCache());
    sTemplate::setActiveTemplate(self::getSetting('template.name','string', 'kusaba-default'));
    sTemplate::setSiteName(self::getSetting('site.name', 'string', __('{Kusaba-Y}: No site name')));
    sTemplate::setSiteSlogan(self::getSetting('site.slogan', 'string', ''));
    sTemplate::setMode(self::isProductionMode() ? 'production' : 'development');

    sTemplate::enableQueryStrings(FALSE);

    sTemplate::addJavaScriptFile('./files/js/modernizr.2.6.1.custom.js', 'head');
    
    foreach (self::$js_files as $file_suffix) {
      sTemplate::addJavaScriptFile('./files/js/'.$file_suffix);
    }

    foreach (self::$minified_js_files as $file_suffix) {
      sTemplate::addMinifiedJavaScriptFile('./files/js/'.$file_suffix);
    }

    foreach (self::$css_files as $file) {
      sTemplate::addCSSFile($file);
    }
  }

  /**
   * @internal
   */
  public static function configureLogging() {
    $log_path = self::getSetting('site.error-log-destination', 'string', '/var/log/sutra/kusaba-y.log');
    
    if (is_file($log_path)) {
      self::$log_file_handle = fopen($log_path, 'a');
    }
    
    register_shutdown_function(self::callback(self::closeLogHandle));
    self::enableErrorHandling($log_path);
    self::enableExceptionHandling($log_path, self::exceptionClosingCallback);
    self::registerDebugCallback(self::debugCallback);
    self::enableDebugging(!self::isProductionMode());
  }

  /**
   * @internal
   */
  public static function closeLogHandle() {
    fclose(self::$log_file_handle);
  }

  public static function main() {
    parent::main();
    
    self::configureLogging();
    self::configureTemplate();
    
    kRouter::run();
  }
}
