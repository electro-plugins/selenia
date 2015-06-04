<?php
namespace Selene\Modules\Admin\Controllers\Users;
use Impactwave\WebConsole\WebConsole;
use Selene\Contracts\UserInterface;
use Selene\DataObject;
use Selene\Exceptions\FatalException;
use Selene\Exceptions\ValidationException;
use Selene\Modules\Admin\Config\AdminModule;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\User as UserModel;
use Selene\Session;

class User extends AdminController
{
  /** Password to display when modifying an existing user. */
  const DUMMY_PASS = 'dummy password';

  protected $pageTitle = '$ADMIN_ADMIN_USER';

  public function action_submit (DataObject $data = null, $param = null)
  {
    /** @var $session Session */
    global $session;

    $settings = AdminModule::settings ();
    $username = $_POST['_username'];
    $password = $_POST['_password'];

    // If the user active checkbox is not shown, $active is always true.
    $isSelf     = $data->id () == $session->user->id ();
    $showActive = !$isSelf && get ($settings, 'activeUsers', true);
    $active     = get ($_POST, '_active', !$showActive);

    $role = get ($_POST, '_role');
    /** @var User $data */
    if (!isset($data))
      throw new FatalException('Can\'t insert/update NULL DataObject.');

    if ($username == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');

    if ($password == self::DUMMY_PASS) $password = '';

    if ($password == '') {
      if ($data->isNew ())
        throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_PASSWORD');
      // Do not change the password if the user already exists and the password field was not modified (or left empty)
      // on the form.
    }
    else $data->password ($password);

    $data->username ($username);
    $data->active ($active);
    if (isset($role))
      $data->role ($role);

    if ($data->isNew ())
      $this->insertData ($data, $param);
    else $this->updateData ($data, $param);
  }

  public function action_delete (DataObject $data = null, $param = null)
  {
    global $session;
    /** @var UserInterface $data */
    parent::action_delete ($data, $param);
    if ($data->id () == $session->user->id)
      $this->action_logout ();
  }

  protected function model ()
  {
    /** @var $session Session */
    global $session, $application;
    $settings = AdminModule::settings ();

    /** @var UserModel $user */
    if (get ($this->sitePage->config, 'self')) {
      $user = $this->dataItem = $session->user;
      $user->read ();
    }
    else {
      $user = $this->dataItem = $this->loadRequested (new $application->userModel);
      if (!$user) {
        _log ('<#section|User>', $user, '</#section>');
        WebConsole::throwErrorWithLog (new FatalException("Cant't find the user."));
      }
    }
    // Set a default role for a new user.
    if (!exists ($user->role ()))
      $user->role (get ($settings, 'defaultRole', UserInterface::USER_ROLE_STANDARD));
  }

  protected function setupViewModel ()
  {
    parent::setupViewModel();
    /** @var $session Session */
    global $session;
    $settings = AdminModule::settings ();

    $user    = $this->dataItem;
    $isDev   = $session->user->role () == UserInterface::USER_ROLE_DEVELOPER;
    $isAdmin = $session->user->role () == UserInterface::USER_ROLE_ADMIN;
    // Has it the Standard or Admin roles?
    $isStandard = $isAdmin || $session->user->role () == UserInterface::USER_ROLE_STANDARD;
    $isSelf     = $user->id () == $session->user->id ();

    $viewModel = [
      '_username'       => $user->username (),
      '_password'       => strlen ($user->password ()) ? self::DUMMY_PASS : '',
      '_active'         => $user->active (),
      '_role'           => $user->role (),
      'isAdmin'         => $isAdmin,
      'isNotAdminOrDev' => !$isAdmin && !$isDev,
      'isDev'           => $isDev,
      'isNotDev'        => !$isDev,
      'isStandard'      => $isStandard,
      'showRoles'       => $isDev || ($isAdmin && get ($settings, 'editRoles', true)),
      'dev_role'        => UserInterface::USER_ROLE_DEVELOPER,
      'admin_role'      => UserInterface::USER_ROLE_ADMIN,
      'standard_role'   => UserInterface::USER_ROLE_STANDARD,
      'guest_role'      => UserInterface::USER_ROLE_GUEST,
      'showActive'      => !$isSelf && get ($settings, 'activeUsers', true),
      'canDelete'       => // Will be either true or null.
        (
          !$user->isNew () &&
          // User is not self or delete self is allowed.
          (!$isSelf || get ($settings, 'allowDeleteSelf', true))
        ) ?: null,
    ];
    $this->setViewModel ('login', $viewModel);
  }

}
