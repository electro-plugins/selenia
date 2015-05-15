<?php
namespace Selene\Modules\Admin\Controllers\Users;
use Impactwave\WebConsole\WebConsole;
use Selene\DataObject;
use Selene\Exceptions\ConfigException;
use Selene\Exceptions\FatalException;
use Selene\Exceptions\ValidationException;
use Selene\Matisse\DataRecord;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\User;
use Selene\Session;

class AdminUserForm extends AdminController
{
  public function action_submit (DataObject $data = null, $param = null)
  {
    /** @var User $data */
    if ($data->username == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');
    if ($data->password == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_PASSWORD');
    _log($data);
    return;
//    parent::action_submit ($data, $param);
    if (!isset($data))
      throw new BaseException('Can\'t insert/update NULL DataObject.', Status::FATAL);
    if ($data->isNew ())
      $this->insertData ($data, $param);
    else $this->updateData ($data, $param);
  }

  public function action_delete (DataObject $data = null, $param = null)
  {
    global $session;
    parent::action_delete ($data, $param);
    if ($data->id () == $session->user->id)
      $this->action_logout ();
  }

  protected function setupModel ()
  {
    /** @var $session Session */
    global $session, $application;

    if (get ($this->sitePage->config, 'self'))
      $this->dataItem = $session->user;
    else {
      $this->dataItem = new $application->userModel;
      $this->applyPresets ();
      if (!$this->dataItem->read ()) {
        _log ($this->dataItem);
        WebConsole::throwErrorWithLog (new FatalException("Cant't find the user."));
      }
    }
    if (empty($this->dataItem->role ()))
      $this->dataItem->role ('standard');
    $this->setDataSource ('default', new DataRecord($this->dataItem));
  }

}
