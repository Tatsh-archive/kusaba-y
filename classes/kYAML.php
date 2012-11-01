<?php
/**
 * YAML parsing. This is a basic class that has no built-in decoder. Because of
 *   this, use of this class requires one of the following extensions or
 *   libraries:
 *
 * - syck (PHP extension)
 * - spyc (library)
 * - Symfony 2's YAML component
 * - pecl-yaml (extension) (not recommended due to compatibility issues)
 *
 * @copyright 2012 Andrew Udvare
 * @author Andrew Udvare <audvare@gmail.com>
 * @license MIT
 *
 * @package KusabaY
 * @link https://github.com/HopelessCode/kusaba-y GitHub repository
 *
 * @link http://whytheluckystiff.net/syck/ Syck
 * @link https://github.com/mustangostang/spyc/ Spyc
 * @link https://github.com/symfony/Yaml Symfony YAML component
 * @link http://pecl.php.net/package/yaml PECL-YAML
 *
 * @version 1.0
 */
class kYAML {
  /**
   * Decoding callback.
   *
   * @var callable
   */
  private static $decode_callback = NULL;

  /**
   * Encoding callback.
   *
   * @var callable
   */
  private static $encode_callback = NULL;

  /**
   * Sets callbacks based on finding one of the used libraries.
   *
   * @throws fProgrammerException If no suitable library is found.
   *
   * @return void
   */
  private static function setCallbacks() {
    if (self::$decode_callback === NULL) {
      // syck -> Spyc -> Symfony -> pecl-yaml
      $spyc_callback = array('Spyc', 'YAMLLoadString');
      $spyc_dump_callback = array('Spyc', 'YAMLDump');
      $sf_yaml_callback = array('Symfony\\Component\\Yaml\\Parser', 'parse');
      $sf_yaml_dump_callback = array('Symfony\\Component\\Yaml\\Dumper', 'dump');

      if (extension_loaded('syck')) {
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
      else {
        throw new fProgrammerException('One of the following libraries or extensions must be installed to decode and encode YAML: %s. There is currently no built-in decoder/encoder in this class.', join(', ', array(
          'syck (PECL)',
          'Spyc (PHP)',
          'Symfony YAML Component (PHP)',
          'yaml (PECL)',
        )));
      }
    }
  }

  /**
   * Decodes a YAML file.
   *
   * @param fFile|string $path Path to the file or an `fFile` object.
   * @return mixed PHP version of the object.
   */
  public static function decodeFile($path) {
    $file = new fFile($path);
    return self::decode($file->read());
  }

  /**
   * Decodes a YAML string.
   *
   * @param string $string The string to parse.
   * @return mixed PHP version of the object.
   */
  public static function decode($string) {
    self::setCallbacks();
    return call_user_func(self::$decode_callback, $string);
  }

  /**
   * Encodes a value to YAML.
   *
   * @param mixed $arg The value to encode.
   * @return string The value encoded as YAML.
   */
  public static function encode($arg) {
    self::setCallbacks();
    return call_user_func(self::$encode_callback, $arg);
  }

  /**
   * Sends a HTTP Content-Type header for YAML.
   *
   * @return void
   */
  public static function sendHeader() {
    header('Content-Type: application/x-yaml; charset=utf-8');
  }
}
