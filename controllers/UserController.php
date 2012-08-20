<?php
class AdminController extends MoorActionController {
  const LOGIN_POST_KEY = 'AdminController::LOGIN_POST_KEY';

  /**
   * @var User
   */
  private static $user = NULL;
  
  public function login() {
    if (fAuthorization::checkLoggedIn()) {
      return fURL::redirect('/admin/');
    }

    if (fRequest::isPost()) {
      return $this->loginPost();
    }

    sRequest::setPostValues(self::LOGIN_POST_KEY);
    
    $content = sTemplate::buffer('login-form', array('csrf' => fRequest::generateCSRFToken()));
    sTemplate::render(array('content' => $content, 'title' => __('Log in')));
  }

  public static function userExists($name) {
    try {
      self::$user = new User(array('name' => $name));
      return TRUE;
    }
    catch (fNotFoundException $e) {}

    return FALSE;
  }

  public static function isCorrectPassword($password) {
    if (self::$user === NULL) {
      return FALSE;
    }

    return fCryptography::checkPasswordHash($password, self::$user->getPasswordHash());
  }

  public function loginPost() {
    try {
      $message = __('User name or password is incorrect.');
      $validation = new fValidation;
      
      $validation->addRequiredFields('name', 'user_password', 'csrf');
      $validation->setCSRFTokenField('csrf');
      $validation->addCallbackRule('name', kCore::callback(__CLASS__.'::userExists'), $message);
      $validation->addCallbackRule('user_password', kCore::callback(__CLASS__.'::isCorrectPassword'), $message);
      $validation->validate();

      if (self::$user instanceof User) {
        self::$user->store(); // Update date_updated timestamp
      }

      sRequest::deletePostValues(self::LOGIN_POST_KEY);

      fAuthorization::setUserAuthLevel(self::$user->getAuthLevel());
      fAuthorization::setUserToken(self::$user);

      fURL::redirect(fAuthorization::getRequestedURL(TRUE, '/admin/'));
    }
    catch (fValidationException $e) {
      sRequest::savePostValues(self::LOGIN_POST_KEY);
      fURL::redirect();
    }
  }

  public function logout() {
    fAuthorization::destroyUserInfo();
    fURL::redirect('/');
  }

  public function index() {
    if (!fAuthorization::checkAuthLevel('admin')) {
      fAuthorization::destroyUserInfo();
      return fURL::redirect('/login/');
    }
    
    sTemplate::render(array('content' => '<p>Will be the admin page!</p>', 'title' => __('Administration')));
  }
}
