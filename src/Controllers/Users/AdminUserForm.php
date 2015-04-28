<?php
namespace Selene\Modules\Admin\Controllers\Users;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Matisse\DataRecord;

class AdminUserForm extends AdminController
{
  protected function setupViewModel ()
  {
    global $session;
    parent::setupViewModel ();
//    $this->dataItem->username = $session->username;
//    $this->dataItem->read ();
//    $this->setDataSource ('default', new DataRecord($this->dataItem));
  }


}
