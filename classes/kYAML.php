<?php
class kYAML {
  private static $decode_callback = NULL;

  private static $encode_callback = NULL;
  
  private static function findCallbacks() {
    if (self::$decode_callback === NULL) {
      // syck -> Spyc -> Symfony -> pecl-yaml
      $spyc_callback = array('Spyc', 'YAMLLoadString');
      $spyc_dump_callback = array('Spyc', 'YAMLDump');
      $sf_yaml_callback = array('Symfony\\Component\\Yaml\\Parser', 'parse');
      $sf_yaml_dump_callback = array('Symfony\\Component\\Yaml\\Dumper', 'dump');
      
      if (!extension_loaded('syck')) {
        self::$decode_callback = 'syck_load';
        self::$encode_callback = 'syck_dump';
      }
      else if (is_callable($spyc_callback)) {
        self::$decode_callback = $spyc_callback;
        self::$encode_callback = $spyc_dump_callback;
      }
      else if (is_callable($sf_yaml_callback)) {
        self::$decode_callback = $sf_yaml_callback;
        self::$encode_callback = $sf_yaml_dump_callback;
      }
      else if (extension_loaded('yaml')) {
        self::$decode_callback = 'yaml_parse';
        self::$encode_callback = 'yaml_emit';
      }
      
      throw new fProgrammerException('One of the following libraries or extensions must be installed to decode and encode YAML: %s. There is currently no built-in decoder/encoder in this class.', join(', ', array(
        'syck (PECL)',
        'Spyc (PHP)',
        'Symfony YAML Component (PHP)',
        'yaml (PECL)',
      )));
    }
  }

  public static function decodeFile($path) {
    $file = new fFile($path);
    return self::decode($file->read());
  }

  public static function decode($string) {
    self::findCallbacks();
    return call_user_func(self::$decode_callback, $string);
  }

  public static function encode($string) {
    self::findCallbacks();
    return call_user_func(self::$encode_callback, $string);
  }

  public static function sendHeader() {
    header('Content-Type: application/x-yaml; charset=utf-8');
  }
}
