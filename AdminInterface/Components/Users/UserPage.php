<?php
namespace Selenia\Plugins\AdminInterface\Components\Users;
use PhpKit\WebConsole\WebConsole;
use Psr\Http\Message\ResponseInterface;
use Selenia\DataObject;
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\Flash\ValidationException;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\RouterInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceModule;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Location;

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
  public $is;
  /**
   * Data extracted from the User model for editiog on the form.
   * @var array
   */
  public $login;
  public $role;
  public $show;
  /** @var UserInterface|DataObject */
  public $user;

  protected $pageTitle = '$ADMIN_ADMIN_USER';

  static function navigation (AdminInterfaceSettings $settings)
  {
    return (new Navigation)
      ->title ('$ADMIN_ADMIN_USER')
      ->visible (N);
  }

  function z()
  {
    return $editingSelf
      ? (new Location)
        ->when ($settings->profile ())
        ->
        ->title ('$LOGIN_PROFILE')
        ->controller (UserPage::ref ())
        ->menuItem (
          function (Location $location) use ($settings) {
            return $location->path == $settings->prefix () . '/user';
          })
        ->view ('users/user.html')
        ->config ([
          'self' => true // Editing the logged-in user.
        ])
      : (new Location)
        ->controller (UserPage::ref ())
        ->view ('users/user.html');
  }

  public function action_delete ($param = null)
  {
    $this->model = $data = $this->user;
    parent::action_delete ($param);
    /** @var UserInterface $data */
    if ($data->id () == $this->session->user ()->id ()) {
      $this->session->logout ();
      return $this->redirection->home ();
    }
  }

  public function action_submit ($param = null)
  {
    $settings = AdminInterfaceModule::settings ();
    $user     = $this->user;
    $data     = $this->model;
//    echo "<pre>";var_dump($user);exit;

    $username = get ($data, 'username');
    $password = get ($data, 'password');
    $role     = get ($data, 'role', false);

    // Is the user being saved the logged-in user?
    $isSelf = $user->id () == $this->session->user ()->id ();

    // If the user active checkbox is not shown, $active is always true.
    $showActive = !$isSelf && $settings->activeUsers ();
    $active     = get ($data, 'active', !$showActive);

    if ($username == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');

    if ($password == self::DUMMY_PASS) $password = '';

    if ($password == '') {
      if ($user->isNew ())
        throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_PASSWORD');
      // Do not change the password if the user already exists and the password field was not modified (or left empty)
      // on the form.
    }
    else $user->password ($password);

    // Check if the username has been changed

    if ($username != $user->username ()) {
      $tmp = clone $user;
      if ($tmp->findByName ($username))
        throw new ValidationException(ValidationException::DUPLICATE_RECORD, '$LOGIN_USERNAME');
      $user->username ($username);
    }

    $user->active ($active);
    $user->role ($role);

    if ($user->isNew ())
      $this->insertData ($user);
    else $this->updateData ($user);

    if ($isSelf) return $this->redirection->to ($settings->adminHomeUrl ());
  }

  protected function model ()
  {
    $settings = AdminInterfaceModule::settings ();
    $mySelf   = $this->session->user ();

    /** @var UserModel $user */
    if (get ($this->activeRoute->config ?: [], 'self')) {
      $user = $mySelf;
      $user->read ();
    }
    else {
      $myRole = $mySelf->role ();
      $user   = $this->loadRequested (new $this->app->userModel);
      if (!$user) {
        _log ('<#section|User>', $user, '</#section>');
        WebConsole::throwErrorWithLog (new FatalException("Cant't find the user."));
      }
      if ($myRole < UserInterface::USER_ROLE_ADMIN && $mySelf->id () != $user->id ())
        // Can't edit other users.
        throw new HttpException (403);
      if ($user->role () > $myRole)
        // Can't edit a user with a higher role.
        throw new HttpException (403);
    }
    // Set a default role for a new user.
    if (!exists ($user->role ()))
      $user->role ($settings->defaultRole ());

    $this->user = $user;

    $login = [
      'id'       => null,
      'username' => $user->username (),
      'password' => strlen ($user->password ()) ? self::DUMMY_PASS : '',
      'active'   => $user->active (),
      'role'     => $user->role (),
    ];

    return $login;
  }

  protected function viewModel ()
  {
    parent::viewModel ();
    $settings = AdminInterfaceModule::settings ();
    $user     = $this->user;
    $mySelf   = $this->session->user ();

    $isDev   = $mySelf->role () == UserInterface::USER_ROLE_DEVELOPER;
    $isAdmin = $mySelf->role () == UserInterface::USER_ROLE_ADMIN;
    // Has the user the Standard or Admin roles?
    $isStandard = $isAdmin || $mySelf->role () == UserInterface::USER_ROLE_STANDARD;
    // Are we editing the logged-in user?
    $isSelf = $user->id () == $mySelf->id ();

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
      'roles'  => $isDev || ($isAdmin && $settings->editRoles ()),
      'active' => !$isSelf && $settings->activeUsers (),
    ];
    $this->canDelete = // Will be either true or null.
      (
        !$user->isNew () &&
        // User is not self or delete self is allowed.
        ($isDev || !$isSelf || $settings->allowDeleteSelf ())
      ) ?: null;
    $this->canRename = $settings->allowRename ();
  }

}
