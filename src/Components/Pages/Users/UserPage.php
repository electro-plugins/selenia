<?php
namespace Selenia\Platform\Components\Pages\Users;

use Electro\Exceptions\FatalException;
use Electro\Exceptions\Flash\ValidationException;
use Electro\Exceptions\HttpException;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Plugins\IlluminateDatabase\Services\User as UserModel;
use Electro\Plugins\Login\Config\LoginSettings;
use Illuminate\Database\Eloquent\Model;
use Selenia\Platform\Components\AdminPageComponent;
use Selenia\Platform\Config\PlatformSettings;

/**
 * Notes:
 * - A user can always edit himself.
 * - ADMIN and DEV users can always rename themselves, others can rename only if enabled via settings.
 * - DEV users can always delete everyone.
 * - ADMIN users can delete others.
 * - Other users can delete themselves if enabled via settings.
 */
class UserPage extends AdminPageComponent
{
  /** Password to display when modifying an existing user. */
  const DUMMY_PASS = 'dummy password';
  public $canDelete;
  public $canRename;
  /**
   * Are we on the profile page?
   * > Set via router.
   *
   * @var bool
   */
  public $editingSelf = false;
  public $is;
  /**
   * Data extracted from the User model for editiog on the form.
   *
   * @var array
   */
  public $login;
  public $role;
  /** @var SessionInterface */
  public    $session;
  public    $show;
  public    $templateUrl    = 'platform/users/user.html';
  protected $autoRedirectUp = true;
  /** @var PlatformSettings */
  private $adminSettings;
  /** @var LoginSettings */
  private $loginSettings;
  /** @var UserInterface|Model */
  private $user;

  public function action_delete ($param = null)
  {
    $this->model = $data = $this->user;
    parent::action_delete ($param);

    /** @var UserInterface $data */
    if ($data->getFields ()['id'] == $this->session->user ()->getFields ()['id']) {
      $this->session->logout ();
      return $this->redirection->home ();
    }
  }

  public function action_submit ($param = null)
  {
    $user = $this->user;
    $data = $this->model;
//    echo "<pre>";var_dump($user);exit;

    $username = get ($data, 'username');
    $email    = get ($data, 'email');
    $password = get ($data, 'password');
    $role     = get ($data, 'role', false);
    $realName = get ($data, 'realName');
		$token           = bin2hex (openssl_random_pseudo_bytes (16));

		// Is the user being saved the logged-in user?
    $isSelf = $user->getFields ()['id'] == $this->session->user ()->getFields ()['id'];

    // If the user active checkbox is not shown, $active is always true.
    $showActive = !$isSelf && $this->adminSettings->enableUsersDisabling ();
    $active     = get ($data, 'active', !$showActive);
    $enabled    = get ($data, 'enabled');

    if ($username == '' && $this->loginSettings->displayUsername)
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');

    if ($this->loginSettings->displayEmail) {
      if ($email == '')
        throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_EMAIL');

      if (!filter_var ($email, FILTER_VALIDATE_EMAIL))
        throw new ValidationException(ValidationException::INVALID_EMAIL, '$LOGIN_EMAIL');
    }

    if ($password == self::DUMMY_PASS) $password = '';

    if ($password == '') {
      if (!$user->exists)
        throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_PASSWORD');
      // Do not change the password if the user already exists and the password field was not modified (or left empty)
      // on the form.
    }

    else $user->mergeFields (['password' => $password]);

    // Check if the username has been changed

    if ($username != $user->getFields ()['username']) {
      $tmp = clone $user;
      if ($tmp->findByName ($username))
        throw new ValidationException(ValidationException::DUPLICATE_RECORD, '$LOGIN_USERNAME');
      $user->mergeFields (['username' => $username]);
    }

    if ($email != $user->getFields ()['email']) {
      $tmp = clone $user;
      if ($tmp->findByEmail ($email))
        throw new ValidationException(ValidationException::DUPLICATE_RECORD, '$LOGIN_EMAIL');
      $user->mergeFields (['email' => $email]);
    }

    $user->mergeFields ([
      'active' => $active, 'enabled' => $enabled,
      'role'   => $role, 'realName' => ($realName ?: ucfirst ($username)),
			'token' => $token
    ]);

    if ($user->submit ())
      $this->session->flashMessage ('$APP_MSG_SAVED');

    if ($isSelf) return $this->redirection->to ($this->session->previousUrl ());
  }

  function inject ()
  {
    return function (PlatformSettings $settings, UserInterface $user, SessionInterface $session, LoginSettings $loginSettings) {
      $this->adminSettings = $settings;
      $this->loginSettings = $loginSettings;
      $this->user          = $user;
      $this->session       = $session;
    };
  }

  protected function model ()
  {
    $mySelf = $this->session->user ();
    /** @var UserModel $user */
    $user     = $this->user;
    $myFields = $mySelf->getFields ();

    if ($this->editingSelf) {
      $id = $myFields['id'];
      $f  = $user->findById ($id);
      if (!$f)
        throw new FatalException ("User $id not found");
      $fields = $user->getFields ();
    }
    else {
      $myRole = $myFields['role'];
      $id     = $this->request->getAttribute ("@id");
      if ($id) {
        $f = $user->findById ($id);
        if (!$f)
          throw new FatalException ("User $id not found");
      }
      $fields = $user->getFields ();
      if ($myRole < UserInterface::USER_ROLE_ADMIN && $myFields['id'] != $fields['id'])
        // Can't edit other users.
        throw new HttpException (403);
      if ($fields['role'] > $myRole)
        // Can't edit a user with a higher role.
        throw new HttpException (403);
    }

    $defaults = [];

    // Set a default role for a new user.
    if (!exists ($fields['role']))
      $defaults['role'] = $this->adminSettings->defaultRole ();

    // Set default 'enabled' for a new user.
    if (!exists ($fields['enabled']))
      $defaults['enabled'] = 1;

    // Set default 'active' for a new user.
    if (!exists ($fields['active']))
      $defaults['active'] = 1;

    array_mergeInto ($fields, $defaults);

    $login = [
      'id'       => null,
      'username' => $fields['username'],
      'email'    => $fields['email'],
      'realName' => $fields['realName'],
      'password' => strlen ($fields['password']) || $id ? self::DUMMY_PASS : '',
      'active'   => $fields['active'],
      'enabled'  => $fields['enabled'],
      'role'     => $fields['role'],
    ];

    $this->modelController->setModel ($login);
  }

  protected function viewModel (ViewModelInterface $viewModel)
  {
    parent::viewModel ($viewModel);

    $user = $viewModel['user'] = $this->user;

    $mySelf   = $this->session->user ();
    $myFields = $mySelf->getFields ();

    $isDev   = $myFields['role'] == UserInterface::USER_ROLE_DEVELOPER;
    $isAdmin = $myFields['role'] == UserInterface::USER_ROLE_ADMIN;
    // Are we editing the logged-in user?
    $isSelf = $user->getFields ()['id'] == $myFields['id'];

    if ($isSelf)
      $this->session->setPreviousUrl ($this->request->getHeaderLine ('Referer'));

    $viewModel['role']      = [
      'dev'      => UserInterface::USER_ROLE_DEVELOPER,
      'admin'    => UserInterface::USER_ROLE_ADMIN,
      'standard' => UserInterface::USER_ROLE_STANDARD,
      'guest'    => UserInterface::USER_ROLE_GUEST,
    ];
    $viewModel['show']      = [
      'roles'  => $isDev || ($isAdmin && $this->adminSettings->allowEditRole ()),
      'active' => !$isSelf && $this->adminSettings->enableUsersDisabling (),
    ];
    $viewModel['canDelete'] = // Will be either true or null.
      (
        $user->exists &&
        // User is not self or delete self is allowed.
        ($isDev || !$isSelf || $this->adminSettings->allowDeleteSelf ())
      ) ?: null;
    $viewModel['canRename'] = $this->adminSettings->allowRename ();
  }
}
