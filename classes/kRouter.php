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
    '/login/' => 'AdminController::login',
    '/admin/' => 'AdminController::index',
    '/logout/' => 'AdminController::logout',
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

      if (!kCore::isProductionMode() || !$urls || !is_array($urls)) {
        $boards = fRecordSet::build('Board');
        $urls = array();
        
        foreach ($boards as $board) {
          $urls[] = $board->getShortURL();
        }
        
        $cache->set('boards.short_urls', $urls);
      }

      // Every databased route must be registered as well
      foreach ($urls as $short_url) {
        Moor::route('/'.$short_url.'/', 'BoardController::index');
        Moor::route('/'.$short_url.'/res/:id(\d+)', 'ThreadController::index'); // Reply path
      }

      Moor::enableRestlessURLs();
      Moor::setNotFoundCallback('NotFoundController::index');
      
      self::$registered = TRUE;
    }
  }

  /**
   * Runs the router.
   *
   * @return void
   */
  public static function run() {
    self::registerRoutes();
    Moor::run();
  }
}
