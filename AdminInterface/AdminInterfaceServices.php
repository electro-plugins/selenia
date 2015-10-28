<?php
namespace Selenia\Plugins\AdminInterface;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceConfig;
use Selenia\Plugins\AdminInterface\Config\AdminModule;

class AdminInterfaceServices implements ModuleInterface
{
  function boot ()
  {
  }

  function configure (ModuleServices $module)
  {
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideTemplates ()
      ->provideViews ()
      ->registerPresets (['Selenia\Plugins\AdminInterface\Config\AdminPresets'])
      ->setDefaultConfig ([
        'main'                            => [
          'userModel'   => 'Selenia\Plugins\AdminInterface\Models\User',
          'loginView'   => 'login.html',
          'translation' => true,
        ],
        'selenia-plugins/admin-interface' => new AdminInterfaceConfig,
      ])
      ->onPostConfig (function () use ($module) {
        $module->registerRoutes ([
          RouteGroup ([
            'title'  => '$ADMIN_MENU_TITLE',
            'prefix' => AdminModule::settings ()->getPrefix (),
            'routes' => AdminModule::routes (),
          ])->activeFor (AdminModule::settings ()->getMenu ()),
        ]);
      });
  }

}
