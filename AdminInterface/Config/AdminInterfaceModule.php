<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Application;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Controllers\Users\User;
use Selenia\Plugins\AdminInterface\Controllers\Users\Users;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Location;

class AdminInterfaceModule implements ModuleInterface
{
  /** @var Application */
  private $app;

  //TODO: replace "dummy URI" below

  /**
   * @return AdminInterfaceSettings
   */
  static function settings ()
  {
    global $application;
    return get ($application->config, 'selenia-plugins/admin-interface');
  }

  function boot ()
  {
  }

  function configure (ModuleServices $module, Application $app)
  {
    $this->app = $app;
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideTemplates ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::ref])
      ->setDefaultConfig ([
        'main'                            => [
          'userModel'   => UserModel::ref (),
          'loginView'   => 'login.html',
          'translation' => true,
        ],
        'selenia-plugins/admin-interface' => new AdminInterfaceSettings,
      ])
      ->onPostConfig (function () use ($module) {
        $module->registerRoutes ([
          self::settings ()->prefix () => (new Location)
            ->title ('$ADMIN_MENU_TITLE')
            ->redirectsTo (self::settings ()->adminHomeUrl ())
            ->menuItem (self::settings ()->menu ())
            ->next ($this->routes ()),
        ]);
      });
  }

  function routes ()
  {
    $module    = 'selenia-plugins/admin-interface';
    $settings  = self::settings ();
    $userModel = $this->app->userModel ?: UserModel::ref ();

    return [

      'users' => (new Location)
        ->when ($settings->users ())
        ->title ('$ADMIN_ADMIN_USERS')
        ->controller (Users::ref ())
        ->view ('users/users.html')
        ->waypoint (x)
        ->viewModel ([
          'mainForm' => 'users/{{r.id}}',
        ])
        ->next ([
          ':id' => (new Location)
            ->menuItem (no)
            ->controller (User::ref ())
            ->view ('users/user.html'),
        ]),

      // This is hidden from the main menu.

      'user' => (new Location)
        ->when ($settings->profile ())
        ->title ('$LOGIN_PROFILE')
        ->controller (User::ref ())
        ->menuItem (function (Location $location) use ($settings) {
          return $location->path == $settings->prefix () . '/user';
        })
        ->view ('users/user.html')
        ->config ([
          'self' => true // Editing the logged-in user.
        ]),

    ];

  }

}
