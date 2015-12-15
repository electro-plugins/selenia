<?php
namespace Selenia\Plugins\AdminInterface\Components\Users;

use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;
use Selenia\Plugins\AdminInterface\Models\User;

/**
 * Notes:
 * - only ADMIN and DEV users can access this page.
 * -
 */
class UsersPage extends AdminPageComponent
{
  public function model ()
  {
    $myRole = $this->session->user ()->role ();
    if ($myRole < UserInterface::USER_ROLE_ADMIN)
      // Can't view other users.
      throw new HttpException (403);
    return array_filter ((new User())->map ((new User)->all (),
      function (UserInterface $user) use ($myRole) {
        // Filter out users of superior level.
        return $user->role () > $myRole
          ? null
          : [
            'id'               => $user->id (),
            'active'           => $user->active (),
            'realName'         => $user->realName (),
            'username'         => $user->username (),
            'registrationDate' => $user->registrationDate (),
            'lastLogin'        => $user->lastLogin (),
            'role'             => $user->role (),
          ];
      }));
  }

}
