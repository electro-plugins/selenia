<?php
namespace Selenia\Platform\Components\Pages\Users;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Exceptions\HttpException;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Selenia\Platform\Components\AdminPageComponent;

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

    $users = $this->session->user ()->getUsers ();
    $users = map ($users, function (UserInterface $user) { return $user->getRecord(); });
    $this->modelController->setModel ($users);
  }

}
