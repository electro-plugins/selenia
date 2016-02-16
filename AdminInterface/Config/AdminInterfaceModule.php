<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationProviderInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Components\Users\UserPage;
use Selenia\Plugins\AdminInterface\Components\Users\UsersPage;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;

class AdminInterfaceModule
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, RequestHandlerInterface
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var RouterInterface */
  private $router;
  /** @var AdminInterfaceSettings */
  private $settings;

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);
    $base = strJoin ($this->settings->urlPrefix (), 'settings...', '/');
    return $this->router
      ->set ([
        $base =>
          [
            when ($this->settings->requireAuthentication (), AuthenticationMiddleware::class),

            when ($this->settings->enableUsersManagement (),
              [
                'users' => factory (function (UsersPage $page) {
                  $page->templateUrl = 'users/users.html';
                  $page->preset ([
                    'mainForm' => 'users/{{r.id}}',
                  ]);
                  return $page;
                }),

                'users/@id' => UserPage::class,

                'profile' => factory (function (UserPage $page) {
                  $page->editingSelf = true;
                  return $page;
                }),
              ]),
          ],
      ])
      ->__invoke ($request, $response, $next);
  }

  function configure (ModuleServices $module, AdminInterfaceSettings $settings, Application $app,
                      RouterInterface $router, RedirectionInterface $redirection)
  {
    $this->settings    = $settings;
    $this->router      = $router;
    $this->redirection = $redirection;
    $app->userModel    = UserModel::class;
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideMacros ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::class])
      ->onPostConfig (function () use ($module) {
        $module
          ->registerRouter ($this)
          ->provideNavigation ($this);
      });
  }

  function defineNavigation (NavigationInterface $navigation)
  {
    $navigation->add ([
      $this->settings->urlPrefix () => $navigation
        ->group ()
        ->id ('app_home')
        ->title ('$APP_HOME')
        ->links ([
          'settings' => $navigation
            ->group ()
            ->id ('settings')
            ->icon ('fa fa-cog')
            ->title ('$APP_USER_MENU')
            ->visible (N)
            ->links ([
              'profile' => $navigation
                ->link ()
                ->id ('profile')
                ->title ('$LOGIN_PROFILE')
                ->icon ('fa fa-user')
                ->visible (Y),
              'users'   => $navigation
                ->link ()
                ->id ('users')
                ->title ('$APP_SETTINGS_USERS')
                ->icon ('fa fa-users')
                ->visible ($this->settings->enableUsersManagement ())
                ->links ([
                  '@id' => $navigation
                    ->link ()
                    ->id ('userForm')
                    ->title ('$APP_SETTINGS_USER')
                    ->visible (N),
                ]),
            ]),
        ]),
    ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (AdminInterfaceSettings::class);
  }

}
