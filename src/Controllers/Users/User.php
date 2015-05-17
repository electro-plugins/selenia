<?php
namespace Selene\Modules\Admin\Controllers\Users;
use Impactwave\WebConsole\WebConsole;
use Selene\Contracts\UserInterface;
use Selene\DataObject;
use Selene\Exceptions\FatalException;
use Selene\Exceptions\ValidationException;
use Selene\Modules\Admin\Config\AdminModule;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Session;

class User extends AdminController
{
  /** Password to display when modifying an existing user. */
  const DUMMY_PASS = 'dummy password';

  public function action_submit (DataObject $data = null, $param = null)
  {
    $settings = AdminModule::settings ();
    $username = $_POST['_username'];
    $password = $_POST['_password'];
    // If !activeUsers, $active is always true.
    $active   = get ($_POST, '_active', !get ($settings, 'activeUsers', true));
    $role     = get ($_POST, '_role');
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

  protected function setupModel ()
  {
    /** @var $session Session */
    global $session, $application;
    $settings = AdminModule::settings ();

    if (get ($this->sitePage->config, 'self'))
      $this->dataItem = $session->user;
    else {
      $this->dataItem = new $application->userModel;
      $this->dataItem->initFromQueryString ();
      $this->applyPresets ();
      if (!$this->dataItem->read ()) {
        _log ('<#section|User>', $this->dataItem, '</#section>');
        WebConsole::throwErrorWithLog (new FatalException("Cant't find the user."));
      }
    }
    // Set a default role for a new user.
    if (!exists ($this->dataItem->role ()))
      $this->dataItem->role (get ($settings, 'defaultRole', UserInterface::USER_ROLE_STANDARD));

    $isAdmin = $this->dataItem->role () == UserInterface::USER_ROLE_ADMIN;
    // Has it the Standard or Admin roles?
    $isStandard = $isAdmin || $this->dataItem->role () == UserInterface::USER_ROLE_STANDARD;
    $isSelf     = $this->dataItem->id () == $session->user->id ();

    $viewModel = [
      '_username'     => $this->dataItem->username (),
      '_password'     => strlen ($this->dataItem->password ()) ? self::DUMMY_PASS : '',
      '_active'       => $this->dataItem->active (),
      '_role'         => $this->dataItem->role (),
      'isAdmin'       => $isAdmin,
      'isStandard'    => $isStandard,
      'showRoles'     => $isStandard && get ($settings, 'editRoles', true),
      'admin_role'    => UserInterface::USER_ROLE_ADMIN,
      'standard_role' => UserInterface::USER_ROLE_STANDARD,
      'guest_role'    => UserInterface::USER_ROLE_GUEST,
      'showActive'    => !$isSelf && get ($settings, 'activeUsers', true),
      'canDelete'     => // Will be either true or null.
        (
          !$this->dataItem->isNew () &&
          // User is not self or delete self is allowed.
          (!$isSelf || get ($settings, 'allowDeleteSelf', true))
        ) ?: null,
    ];
    $this->setViewModel ('login', $viewModel);
  }

}
