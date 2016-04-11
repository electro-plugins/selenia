<?php
namespace Selenia\Plugins\AdminInterface\Components\Pages\Users;

use Illuminate\Database\Eloquent\Model;
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\Flash\ValidationException;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;

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

  /** @var AdminInterfaceSettings */
  public $adminSettings;

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
  public $session;
  public $show;
  public $templateUrl = 'adminInterface/users/user.html';
  /** @var UserInterface|Model */
  public $user;
  protected $autoRedirectUp = true;

  public function action_delete ($param = null)
  {
    $this->model = $data = $this->user;
    parent::action_delete ($param);
    /** @var UserInterface $data */
    if ($data->idField () == $this->session->user ()->idField ()) {
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
    $password = get ($data, 'password');
    $role     = get ($data, 'role', false);

    // Is the user being saved the logged-in user?
    $isSelf = $user->idField () == $this->session->user ()->idField ();

    // If the user active checkbox is not shown, $active is always true.
    $showActive = !$isSelf && $this->adminSettings->enableUsersDisabling ();
    $active     = get ($data, 'active', !$showActive);

    if ($username == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');

    if ($password == self::DUMMY_PASS) $password = '';

    if ($password == '') {
      if (!$user->exists)
        throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_PASSWORD');
      // Do not change the password if the user already exists and the password field was not modified (or left empty)
      // on the form.
    }
    else $user->passwordField ($password);

    // Check if the username has been changed

    if ($username != $user->usernameField ()) {
      $tmp = clone $user;
      if ($tmp->findByName ($username))
        throw new ValidationException(ValidationException::DUPLICATE_RECORD, '$LOGIN_USERNAME');
      $user->usernameField ($username);
    }

    $user->activeField ($active);
    $user->roleField ($role);

    if ($user->save ())
      $this->session->flashMessage ('$APP_MSG_SAVED');

    if ($isSelf) return $this->redirection->to ($this->session->previousUrl ());
  }

  function inject ()
  {
    return function (AdminInterfaceSettings $settings, UserInterface $user, SessionInterface $session) {
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
      $id = $mySelf->idField ();
      $f  = $user->findById ($id);
      if (!$f)
        throw new FatalException ("User $id not found");
    }
    else {
      $myRole = $mySelf->roleField ();
      $id     = $this->request->getAttribute ("@id");
      if ($id) {
        $f = $user->findById ($id);
        if (!$f)
          throw new FatalException ("User $id not found");
      }
      if ($myRole < UserInterface::USER_ROLE_ADMIN && $mySelf->idField () != $user->idField ())
        // Can't edit other users.
        throw new HttpException (403);
      if ($user->roleField () > $myRole)
        // Can't edit a user with a higher role.
        throw new HttpException (403);
    }
    // Set a default role for a new user.
    if (!exists ($user->roleField ()))
      $user->roleField ($this->adminSettings->defaultRole ());

    $login = [
      'id'       => null,
      'username' => $user->usernameField (),
      'password' => strlen ($user->passwordField ()) ? self::DUMMY_PASS : '',
      'active'   => $user->activeField (),
      'role'     => $user->roleField (),
    ];

    return $login;
  }

  protected function viewModel ()
  {
    parent::viewModel ();
    $user   = $this->user;
    $mySelf = $this->session->user ();

    $isDev   = $mySelf->roleField () == UserInterface::USER_ROLE_DEVELOPER;
    $isAdmin = $mySelf->roleField () == UserInterface::USER_ROLE_ADMIN;
    // Has the user the Standard or Admin roles?
    $isStandard = $isAdmin || $mySelf->roleField () == UserInterface::USER_ROLE_STANDARD;
    // Are we editing the logged-in user?
    $isSelf = $user->idField () == $mySelf->idField ();

    if ($isSelf)
      $this->session->setPreviousUrl ($this->request->getHeaderLine ('Referer'));

    $this->is        = [
      'admin'    => $isAdmin,
      'dev'      => $isDev,
      'standard' => $isStandard,
      'self'     => $isSelf,
    ];
    $this->role      = [
      'dev'      => UserInterface::USER_ROLE_DEVELOPER,
      'admin'    => UserInterface::USER_ROLE_ADMIN,
      'standard' => UserInterface::USER_ROLE_STANDARD,
      'guest'    => UserInterface::USER_ROLE_GUEST,
    ];
    $this->show      = [
      'roles'  => $isDev || ($isAdmin && $this->adminSettings->allowEditRole ()),
      'active' => !$isSelf && $this->adminSettings->enableUsersDisabling (),
    ];
    $this->canDelete = // Will be either true or null.
      (
        $user->exists &&
        // User is not self or delete self is allowed.
        ($isDev || !$isSelf || $this->adminSettings->allowDeleteSelf ())
      ) ?: null;
    $this->canRename = $this->adminSettings->allowRename ();
  }

}
