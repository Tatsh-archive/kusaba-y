<?php
class kHTML extends sHTML {
  private static $purifier = NULL;
  
  /**
   * @return HTMLPurifier
   */
  private static function setPurifier() {
    if (self::$purifier === NULL) {
      $config = HTMLPurifier_Config::createDefault();
      $config->set('Cache.SerializerPath', kCore::getSetting('htmlpurifier.serializer_path', 'string', './files'));
      $config->set('AutoFormat.AutoParagraph', TRUE);
      self::$purifier = new HTMLPurifier($config);
    }
    
    return self::$purifier;
  }

  /**
   * @param string $string
   * 
   * @return string
   */
  public static function prepare($string) {
    self::setPurifier();
    return self::$purifier->purify(fHTML::prepare($string));
  }
}
