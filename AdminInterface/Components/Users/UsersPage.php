<?php
namespace Selenia\Plugins\AdminInterface\Components\Users;

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
    $myRole = $this->session->user ()->roleField ();
    if ($myRole < UserInterface::USER_ROLE_ADMIN)
      // Can't view other users.
      throw new HttpException (403);

    $class = $this->app->userModel;
    $users = $class::orderBy ('username')->get (); //TODO: order by custom username column
    return filter ($users, function (UserInterface $user) use ($myRole) {
      return $user->roleField () <= $myRole;
    });
  }

}
