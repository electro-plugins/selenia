<?php
namespace Selenia\Platform\Components\Pages\Users;

use Electro\Exceptions\FatalException;
use Electro\Exceptions\Flash\ValidationException;
use Electro\Exceptions\HttpException;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interop\ViewModel;
use Illuminate\Database\Eloquent\Model;
use Selenia\Platform\Components\AdminPageComponent;
use Selenia\Platform\Config\PlatformSettings;
use Selenia\Platform\Models\User as UserModel;

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
    $realName = get ($data, 'realName');

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
    $user->realNameField ($realName ?: ucfirst ($username));

    if ($user->save ())
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
      'realName' => $user->realNameField (),
      'password' => strlen ($user->passwordField ()) || $id ? self::DUMMY_PASS : '',
      'active'   => $user->activeField (),
      'role'     => $user->roleField (),
    ];

    $this->modelController->setModel ($login);
  }

  protected function viewModel (ViewModelInterface $viewModel)
  {
    parent::viewModel($viewModel);

    $user   = $viewModel->user = $this->user;
    $mySelf = $this->session->user ();

    $isDev   = $mySelf->roleField () == UserInterface::USER_ROLE_DEVELOPER;
    $isAdmin = $mySelf->roleField () == UserInterface::USER_ROLE_ADMIN;
    // Are we editing the logged-in user?
    $isSelf = $user->idField () == $mySelf->idField ();

    if ($isSelf)
      $this->session->setPreviousUrl ($this->request->getHeaderLine ('Referer'));

    $viewModel->role      = [
      'dev'      => UserInterface::USER_ROLE_DEVELOPER,
      'admin'    => UserInterface::USER_ROLE_ADMIN,
      'standard' => UserInterface::USER_ROLE_STANDARD,
      'guest'    => UserInterface::USER_ROLE_GUEST,
    ];
    $viewModel->show      = [
      'roles'  => $isDev || ($isAdmin && $this->adminSettings->allowEditRole ()),
      'active' => !$isSelf && $this->adminSettings->enableUsersDisabling (),
    ];
    $viewModel->canDelete = // Will be either true or null.
      (
        $user->exists &&
        // User is not self or delete self is allowed.
        ($isDev || !$isSelf || $this->adminSettings->allowDeleteSelf ())
      ) ?: null;
    $viewModel->canRename = $this->adminSettings->allowRename ();
  }

}
