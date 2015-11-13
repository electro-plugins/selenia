<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Application;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\NavigationProviderInterface;
use Selenia\Interfaces\RoutableInterface;
use Selenia\Interfaces\RouterInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Components\Users\UserPage;
use Selenia\Plugins\AdminInterface\Components\Users\UsersPage;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Navigation;

class AdminInterfaceModule
  implements ModuleInterface, ServiceProviderInterface, RoutableInterface, NavigationProviderInterface
{
  /** @var AdminInterfaceSettings */
  private $settings;

  function __invoke (RouterInterface $router)
  {
    return $router->matchPrefix ($this->settings->urlPrefix (),
      function (RouterInterface $router) {
        $router
          ->on ('GET')
          ? $router->redirection ()->to ($this->settings->adminHomeUrl ())
          : $router
          ->middleware ($this->settings->requireAuthentication () ? AuthenticationMiddleware::class : null)
          ->dispatch ([
            'users' => UsersPage::class,
            'user'  => function (UserPage $page) {
              $page->editingSelf = true;
              return $page;
            },
          ]);
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
