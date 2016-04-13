<?php
namespace Selenia\Plugins\AdminInterface\Components\Pages\Users;

use Selenia\Authentication\Config\AuthenticationSettings;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;

/**
 * Notes:
 * - only ADMIN and DEV users can access this page.
 * -
 */
class UsersPage extends AdminPageComponent
{
  /** @var SessionInterface */
  public $session;
  /** @var string */
  private $userModel;

  function inject ()
  {
    return function (AuthenticationSettings $settings, SessionInterface $session) {
      $this->userModel = $settings->userModel ();
      $this->session   = $session;
    };
  }

  public function model ()
  {
    $myRole = $this->session->user ()->roleField ();
    if ($myRole < UserInterface::USER_ROLE_ADMIN)
      // Can't view other users.
      throw new HttpException (403);

    $class = $this->userModel;
    $users = $class::orderBy ('username')->get (); //TODO: order by custom username column
    $this->modelController->setModel (filter ($users, function (UserInterface $user) use ($myRole) {
      return $user->roleField () <= $myRole;
    }));
  }

}
