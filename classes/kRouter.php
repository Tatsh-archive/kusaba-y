<?php
/**
 * Routing functionality for organisational purposes.
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
class kRouter {
  /**
   * Routes.
   *
   * @var array
   */
  private static $routes = array(
    '/' => 'FrontController::index',
    '/admin/' => 'AdminController::index',
    '/admin/boards/' => 'BoardController::admin',
    '/login/' => 'AdminController::login',
    '/logout/' => 'AdminController::logout',
    '/not-found/' => 'NotFoundController::index',
  );

  /**
   * If routes have been registered.
   *
   * @var boolean
   */
  private static $registered = FALSE;

  /**
   * Registers routes with Moor.
   *
   * @return void
   */
  public static function registerRoutes() {
    if (self::$registered === FALSE) {
      foreach (self::$routes as $path => $cb) {
        Moor::route($path, $cb);
      }

      $cache = kCore::getCache();
      $urls = $cache->get('boards.short_urls');
      $is_production_mode = kCore::isProductionMode();

      if (!$is_production_mode || !$urls || !is_array($urls)) {
        $boards = fRecordSet::build('Board');
        $urls = array();

        foreach ($boards as $board) {
          $urls[] = $board->getShortURL();
        }

        if ($is_production_mode) {
          $cache->set('boards.short_urls', $urls);
        }
      }

      // Every databased route must be registered as well
      foreach ($urls as $short_url) {
        Moor::route('/'.$short_url.'/', 'BoardController::index');
        Moor::route('/'.$short_url.'/res/:id(\d+)', 'ThreadController::index'); // Reply path
        Moor::route('/admin/boards/'.$short_url.'/', 'BoardController::adminEditBoard');
      }

      Moor::enableRestlessURLs();
      Moor::setNotFoundCallback('NotFoundController::index');

      self::$registered = TRUE;
    }
  }

  /**
   * Redirects URLs with any known PHP GUID strings.
   *
   * @return void
   */
  private static function redirectGUID() {
    $guids = array(
      preg_quote('PHPE9568F34-D428-11d2-A769-00AA001ACF42', '/'),
      preg_quote('PHPE9568F36-D428-11d2-A769-00AA001ACF42', '/'),
      preg_quote('PHPE9568F35-D428-11d2-A769-00AA001ACF42', '/'),
      preg_quote('PHPB8B5F2A0-3C92-11d3-A3A9-4C7B08C10000', '/'),
    );
    $url = fURL::getWithQueryString();

    foreach ($guids as $guid) {
      if (preg_match('/\??\='.$guid.'/', $url)) {
        $redirect = fURL::removeFromQueryString('='.$guid);
        if ($redirect === '?') {
          $redirect = fURL::get();
        }
        return fURL::redirect($redirect);
      }
    }
  }

  /**
   * Runs the router.
   *
   * @return void
   */
  public static function run() {
    self::registerRoutes();
    self::redirectGUID();
    Moor::run();
  }
}
