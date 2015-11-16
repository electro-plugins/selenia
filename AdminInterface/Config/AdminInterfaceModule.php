<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\MiddlewareInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\NavigationProviderInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Components\Users\UserPage;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Navigation;

class AdminInterfaceModule
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, MiddlewareInterface
{
  /** @var AdminInterfaceSettings */
  private $settings;


  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $router = $this->router;
    return $router
      ->for ($request, $response, $next)
      ->route (function () {
        yield $this->settings->urlPrefix () => function () {
          if ($this->settings->requireAuthentication ())
            yield  AuthenticationMiddleware::class;
          yield 'GET @' => function () { return $this->redirection->to ($this->settings->adminHomeUrl ()); };
          if ($this->settings->enableUsersManagement ())
            yield 'users' => function () {
              yield 'GET @' => function (UsersPage $page) {
                $page->templateUrl = 'users/users.html';
                $page->preset ([
                  'mainForm' => 'users/{{r.id}}',
                ]);
                return $page;
              };
              yield '{id}' => UserPage::class;
            };
          yield 'user' => function (UserPage $page) {
            $page->editingSelf = true;
            return $page;
          };
        };
      });
  }

  function configure (ModuleServices $module, AdminInterfaceSettings $settings, Application $app)
  {
    $this->settings = $settings;
    $app->userModel = UserModel::class;
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideTemplates ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::class])
      ->onPostConfig (function () use ($module, $settings) {
        $module
          ->provideNavigation ($this)
          ->registerRouter ($this);
      });
  }

  function getNavigation ()
  {
    return [
      $this->settings->urlPrefix () => (new Navigation)
        ->title ('$ADMIN_MENU_TITLE')
        ->visible ($this->settings->showMenu ())
        ->next ([
          'users'   => (new Navigation)
            ->title ('$ADMIN_ADMIN_USERS')
            ->visible ($this->settings->enableUsersManagement ())
            ->next ([
              '{userId}' => (new Navigation)
                ->title ('$ADMIN_ADMIN_USER')
                ->visible (N),
            ]),
          'profile' => (new Navigation)
            ->title ('$LOGIN_PROFILE')
            ->visible (N),
        ]),
    ];
  }

  /**
   * Registers services on the provided dependency injector.
   * > **Best practice:** do not use the injector to fetch dependencies here.
   * @param InjectorInterface $injector
   */
  function register (InjectorInterface $injector)
  {
    $injector->share (AdminInterfaceSettings::class);
  }

}
