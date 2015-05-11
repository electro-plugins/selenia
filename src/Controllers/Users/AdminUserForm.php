<?php
namespace Selene\Modules\Admin\Controllers\Users;
use Selene\DataObject;
use Selene\Exceptions\ValidationException;
use Selene\Matisse\DataRecord;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\User;
use Selene\Session;

class AdminUserForm extends AdminController
{
  public function action_submit (User $data = null, $param = null)
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

  protected function setupModel ()
  {
    /** @var $session Session */
    global $session;
    parent::setupModel ();
    if (empty($this->dataItem->type))
      $this->dataItem->type = 'standard';
    if (get($this->sitePage->config,'self')) {
      $this->dataItem = $session->user();
      $this->setDataSource ('default', new DataRecord($this->dataItem));
    }
  }


}
