<?php
namespace Selene\Modules\Admin\Controllers\Users;
use Selene\Contracts\UserInterface;
use Selene\Modules\Admin\Controllers\AdminController;

class Users extends AdminController
{
  public function interceptViewDataSet ($dataSourceName, array &$data)
  {
    $data = $this->dataItem->map ($data, function (UserInterface $user) {
      return [
        'id'               => $user->id (),
        'active'           => $user->active (),
        'realName'         => $user->realName (),
        'username'         => $user->username (),
        'registrationDate' => $user->registrationDate (),
        'lastLogin'        => $user->lastLogin (),
        'role'             => $user->role ()
      ];
    });
  }


}
