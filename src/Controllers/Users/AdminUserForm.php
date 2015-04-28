<?php
namespace Selene\Modules\Admin\Controllers\Users;
use DataObject;
use Selene\Matisse\DataRecord;
use Selene\Modules\Admin\Controllers\AdminController;
use ValidationException;

class AdminUserForm extends AdminController
{
  public function action_submit (DataObject $data = null, $param = null)
  {
    if ($data->password == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, 'password');
    parent::action_submit ($data, $param);
  }

  public function action_delete (DataObject $data = null, $param = null)
  {
    global $session;
    parent::action_delete ($data, $param);
    if ($data->username == $session->username)
      $this->action_logout ();
  }

  protected function setupViewModel ()
  {
    global $session;
    parent::setupViewModel ();
    if ($this->moduleLoader->virtualURI == 'user') {
      $this->dataItem->username = $session->username;
      $this->dataItem->read ();
      $this->setDataSource ('default', new DataRecord($this->dataItem));
    }
  }


}
