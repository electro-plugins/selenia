<?php
namespace Selenia\Plugins\AdminInterface;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceConfig;
use Selenia\Plugins\AdminInterface\Config\AdminModule;

class AdminInterfaceServices implements ServiceProviderInterface
{
  function boot () { }

  function register (InjectorInterface $injector)
  {
    ModuleOptions (dirname (__DIR__), [
      'public'    => 'modules/selenia-plugins/admin-interface',
      'lang'      => true,
      'templates' => true,
      'views'     => true,
      'presets'   => ['Selenia\Plugins\AdminInterface\Config\AdminPresets'],
      'config'    => [
        'main'                            => [
          'userModel'   => 'Selenia\Plugins\AdminInterface\Models\User',
          'loginView'   => 'login.html',
          'translation' => true,
        ],
        'selenia-plugins/admin-interface' => new AdminInterfaceConfig,
      ],
    ], function () {
      return [
        'routes' => [
          RouteGroup ([
            'title'  => '$ADMIN_MENU_TITLE',
            'prefix' => AdminModule::settings ()->getPrefix (),
            'routes' => AdminModule::routes (),
          ])->activeFor (AdminModule::settings ()->getMenu ()),
        ],
      ];
    });
  }

}
