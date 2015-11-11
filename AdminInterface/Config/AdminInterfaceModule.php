<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\RoutableInterface;
use Selenia\Interfaces\RouterInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Components\Users\UserPage;
use Selenia\Plugins\AdminInterface\Components\Users\UsersPage;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Location;

class AdminInterfaceModule implements ModuleInterface, ServiceProviderInterface, RoutableInterface
{
  /**
   * @return AdminInterfaceSettings
   */
  static function settings ()
  {
    global $application;
    return get ($application->config, 'selenia-plugins/admin-interface');
  }

  /**
   * @param RouterInterface $router
   * @return ResponseInterface
   */
  function __invoke (RouterInterface $router)
  {
    return $router->route ()->target ()
      ? $router->redirection ()->to (self::settings ()->adminHomeUrl ())
      : $router->dispatch ([
        'users' => UsersPage::class,
        'user'  => UserPage::class,
      ])
        ?: $router->next ();
  }

  function configure (ModuleServices $module, AdminInterfaceSettings $settings)
  {
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideTemplates ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::ref])
      ->setDefaultConfig ([
        'main' => [
          'userModel'   => UserModel::ref (),
          'loginView'   => 'login.html',
          'translation' => true,
        ],
      ])
      ->onPostConfig (function () use ($module, $settings) {
        $module->navigation (
          [
            self::settings ()->prefix () => (new Waypoint)
              ->title ('$ADMIN_MENU_TITLE')
              ->visible (self::settings ()->menu ())
              ->next ([
                'users' => UsersPage::class,
                'user'  => UserPage::class,
              ]),
          ]);
        $module->router (function (RouterInterface $router) {
          return $router->match (self::settings ()->prefix ())
            ? $router->next ()->dispatch ([
              'users' => UsersPage::class,
              'user'  => UserPage::class,
            ])
            : $router->proceed ();
        });
      });
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
