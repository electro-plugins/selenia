<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Controllers\Users\UserController;
use Selenia\Plugins\AdminInterface\Controllers\Users\UsersController;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Location;

class AdminInterfaceModule implements ModuleInterface, ServiceProviderInterface
{
  /**
   * @return AdminInterfaceSettings
   */
  static function settings ()
  {
    global $application;
    return get ($application->config, 'selenia-plugins/admin-interface');
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
                'users' => UsersController::class,
                'user'  => UserController::class
              ]),
          ]);
        $module->registerRoutes (
          [
            self::settings ()->prefix () => (new Location)
              ->redirectsTo (self::settings ()->adminHomeUrl ())
              ->next ([
                'users' => UsersController::routes ($settings),
                'user'  => UserController::routes ($settings, true), // This is hidden from the main menu.
              ]),
          ]);
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
