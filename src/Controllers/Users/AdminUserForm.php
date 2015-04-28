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
    if ($data->username == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_USERNAME');
    if ($data->password == '')
      throw new ValidationException(ValidationException::REQUIRED_FIELD, '$LOGIN_PASSWORD');
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
    if (empty($this->dataItem->type))
      $this->dataItem->type = 'standard';
    if ($this->moduleLoader->virtualURI == 'user') {
      $this->dataItem->username = $session->username;
      $this->dataItem->read ();
      $this->setDataSource ('default', new DataRecord($this->dataItem));
    }
  }


}
