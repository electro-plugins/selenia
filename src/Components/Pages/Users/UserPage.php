<?php
namespace Selenia\Platform\Components\Pages\Users;

use Electro\Exceptions\FatalException;
use Electro\Exceptions\Flash\ValidationException;
use Electro\Exceptions\HttpException;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Plugins\IlluminateDatabase\Services\User as UserModel;
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

    // Is the user being saved the logged-in user?
    $isSelf = $user->getFields ()['id'] == $this->session->user ()->getFields ()['id'];

    // If the user active checkbox is not shown, $active is always true.
    $showActive = !$isSelf && $this->adminSettings->enableUsersDisabling ();
    $active     = get ($data, 'active', !$showActive);
    $enabled    = get ($data, 'enabled');

    if ($username == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');

    if ($email == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_EMAIL');

    if (!filter_var ($email, FILTER_VALIDATE_EMAIL))
      throw new ValidationException(ValidationException::INVALID_EMAIL, '$LOGIN_EMAIL');

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
    ]);

    if ($user->submit ())
      $this->session->flashMessage ('$APP_MSG_SAVED');

    if ($isSelf) return $this->redirection->to ($this->session->previousUrl ());
  }

  function inject ()
  {
    return function (PlatformSettings $settings, UserInterface $user, SessionInterface $session) {
      $this->adminSettings = $settings;
      $this->user          = $user;
      $this->session       = $session;
    };
  }

  protected function model ()
  {
    $mySelf = $this->session->user ();
    $user   = $this->user;

    /** @var UserModel $user */
    if ($this->editingSelf) {
      $id = $mySelf->getFields ()['id'];
      $f  = $user->findById ($id);
      if (!$f)
        throw new FatalException ("User $id not found");
    }
    else {
      $myRole = $mySelf->getFields ()['role'];
      $id     = $this->request->getAttribute ("@id");
      if ($id) {
        $f = $user->findById ($id);
        if (!$f)
          throw new FatalException ("User $id not found");
      }
      if ($myRole < UserInterface::USER_ROLE_ADMIN && $mySelf->getFields ()['id'] != $user->getFields ()['id'])
        // Can't edit other users.
        throw new HttpException (403);
      if ($user->getFields ()['role'] > $myRole)
        // Can't edit a user with a higher role.
        throw new HttpException (403);
    }

    // Set a default role for a new user.
    if (!exists ($user->getFields ()['role']))
      $user->mergeFields (['role' => $this->adminSettings->defaultRole ()]);

    // Set default 'enabled' for a new user.
    if (!exists ($user->getFields ()['enabled']))
      $user->mergeFields (['enabled' => 1]);

    // Set default 'active' for a new user.
    if (!exists ($user->getFields ()['active']))
      $user->mergeFields (['active' => 1]);

    $login = [
      'id'       => null,
      'username' => $user->getFields ()['username'],
      'email'    => $user->getFields ()['email'],
      'realName' => $user->getFields ()['realName'],
      'password' => strlen ($user->getFields ()['password']) || $id ? self::DUMMY_PASS : '',
      'active'   => $user->getFields ()['active'],
      'enabled'  => $user->getFields ()['enabled'],
      'role'     => $user->getFields ()['role'],
    ];

    $this->modelController->setModel ($login);
  }

  protected function viewModel (ViewModelInterface $viewModel)
  {
    parent::viewModel ($viewModel);

    $user = $viewModel['user'] = $this->user;

    $mySelf = $this->session->user ();

    $isDev   = $mySelf->getFields ()['role'] == UserInterface::USER_ROLE_DEVELOPER;
    $isAdmin = $mySelf->getFields ()['role'] == UserInterface::USER_ROLE_ADMIN;
    // Are we editing the logged-in user?
    $isSelf = $user->getFields ()['id'] == $mySelf->getFields ()['id'];

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

    $viewModel['oldActive']   = $this->session->getOldInput ('model/active');
    $viewModel['oldEnabled']  = $this->session->getOldInput ('model/enabled');
    $viewModel['oldUsername'] = $this->session->getOldInput ('model/username');
    $viewModel['oldEmail']    = $this->session->getOldInput ('model/email');
    $viewModel['oldPassword'] = $this->session->getOldInput ('model/password');
    $viewModel['oldRealName'] = $this->session->getOldInput ('model/realName');
    $viewModel['oldRole']     = $this->session->getOldInput ('model/role');
  }
}
