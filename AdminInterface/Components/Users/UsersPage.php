<?php
namespace Selenia\Plugins\AdminInterface\Components\Users;

use PDO;
use Selenia\DataObject;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;

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

    $users = $this->sql->query ("SELECT * FROM users ORDER BY username")->fetchAll ();
    return array_filter ( //remove nulls
      map ($users, function (array $u) use ($myRole) {
        /** @var UserInterface|DataObject $user */
        $user = $this->createModel($this->app->userModel);
        $user->loadFrom($u);
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
