<?php
namespace Selenia\Plugins\AdminInterface\Controllers\Users;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Controllers\AdminController;
use Selenia\Plugins\AdminInterface\Models\User;

class Users extends AdminController
{
  public function model ()
  {
    return (new User())->map((new User)->all (), function (UserInterface $user) {
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
