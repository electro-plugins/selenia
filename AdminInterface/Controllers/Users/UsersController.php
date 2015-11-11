<?php
namespace Selenia\Plugins\AdminInterface\Controllers\Users;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\RouteInterface;
use Selenia\Interfaces\RouterInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;
use Selenia\Plugins\AdminInterface\Controllers\AdminController;
use Selenia\Plugins\AdminInterface\Models\User;
use Selenia\Routing\Location;

/**
 * Notes:
 * - only ADMIN and DEV users can access this page.
 * -
 */
class UsersController extends AdminController implements RouterInterface
{
  static function navigation (AdminInterfaceSettings $settings)
  {
    return (new Navigation)
      ->title ('$ADMIN_ADMIN_USERS')
      ->visible ($settings->users ())
      ->next ([
        UserController::class
      ]);
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, RouteInterface $route)
  {
    return $route
      ->whenMatches ([$this, 'run'])
      ->param ('id', UserController::class)
      ->next ();
  }

  function route (RouterInterface $router)
  {
    if ($settings->users ())
      $router->on (':id', UserController::class);

    return (new Location)
      ->when ($settings->users ())
      ->title ('$ADMIN_ADMIN_USERS')
      ->controller (UsersController::ref ())
      ->view ('users/users.html')
      ->waypoint (Y)
      ->viewModel ([
        'mainForm' => 'users/{{r.id}}',
      ])
      ->next ([
        ':id' => UserController::routes ($settings),
      ]);
  }

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
