<?php
/**
 * Manages loading of classes.
 *
 * @copyright Copyright (c) 2012 Andrew Udvare.
 * @author Andrew Udvare [au] <andrew@bne1.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 *
 * @package KusabaY
 * @link http://www.kusaba.org/
 *
 * @version 1.0
 */
class kLoader extends sLoader {
  const best  = 'kLoader::best';
  const eagar = 'kLoader::eagar';
  const lazy  = 'kLoader::lazy';

  /**
   * Path where libraries are installed.
   *
   * @var string
   */
  private static $path = '';

  /**
   * Path to model classes.
   *
   * @var string
   */
  private static $model_classes_path = '';

  /**
   * Path to router classes.
   *
   * @var string
   */
  private static $router_classes_path = '';

  /**
   * All the library classes.
   *
   * @var array
   */
  private static $classes = array(
    'kCore',
    'kRouter',
    'kYAML',
  );

  private static $model_classes = array(
    'Article',
    'Board',
    'BoardRule',
    'Category',
    'File',
    'FAQ',
    'ImageFile',
    'SiteSetting',
    'Thread',
    'Reply',
    'User',
  );

  /**
   * Router classes.
   *
   * @var array
   */
  private static $router_classes = array(
    'BoardController',
    'FrontController',
    'NotFoundController',
    'ThreadController',
    'UserController',
  );

  /**
   * Override best() method.
   *
   * @return void
   * @see dLoader::eagar()
   */
  public static function best() {
    parent::best();

    if (self::hasOpcodeCache()) {
      return kLoader::eagar();
    }

    self::lazy();
  }

  /**
   * Creates constructor functions.
   *
   * @return void
   */
  private static function createConstructorFunctions() {
  }

  /**
   * Override eager() method to load these classes after Flourish and Sutra's.
   *   Also includes HTMLPurifier.
   *
   * @return void
   */
  public static function eagar() {
    self::setPath();
    self::createConstructorFunctions();

    foreach (self::$classes as $class) {
      require self::$path.$class.'.php';
    }

    foreach (self::$model_classes as $class) {
      require self::$model_classes_path.$class.'.php';
    }

    foreach (self::$router_classes as $class) {
      require self::$router_classes_path.$class.'.php';
    }
  }

  /**
   * Determines where this file is installed.
   *
   * @return void
   */
  private static function setPath() {
    if (!self::$path) {
      self::$path = realpath(dirname(__FILE__)).'/';
      self::$model_classes_path = realpath(self::$path.'../model').'/';
      self::$router_classes_path = realpath(self::$path.'../controllers').'/';
    }
  }

  /**
   * Registers a class auto-loader to load Sutra classes. Also adds HTMLPurifier's
   *   auto-loader.
   *
   * @return void
   */
  public static function lazy() {
    self::setPath();
    self::createConstructorFunctions();

    spl_autoload_register(array('dLoader', 'autoload'));
  }

  /**
   * Tries to load a Sutra demo class.
   *
   * @internal
   *
   * @param  string $class The class to load.
   * @return void
   */
  public static function autoload($class) {
    if ($class[0] != 'k' || ord($class[1]) < 65 || ord($class[1]) > 90) {
      return;
    }

    if (!in_array($class, self::$classes)) {
      return;
    }

    require self::$path.$class.'.php';
  }

  // @codeCoverageIgnoreStart
  /**
   * Forces use as a static class.
   *
   * @return sLoader
   */
  private function __construct() {}
  // @codeCoverageIgnoreEnd
}

/**
 * Copyright (c) 2012 Andrew Udvare <andrew@bne1.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
