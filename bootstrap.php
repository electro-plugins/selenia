<?php

use Selenia\Plugins\AdminInterface\Config\AdminModule;

ModuleOptions (__DIR__, [
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
    'selenia-plugins/admin-interface' => [
      'prefix'          => 'admin',
      'menu'            => true,
      'users'           => true,
      'profile'         => true,
      'editRoles'       => true,
      'defaultRole'     => 'standard',
      'activeUsers'     => true,
      'translations'    => true, // Translations management
      'allowDeleteSelf' => true,
      'footer'          => '{{ !application.appName }} &nbsp;-&nbsp; Copyright &copy; <a href="http://impactwave.com">Impactwave, Lda</a>. All rights reserved.',
    ],
  ],
], function () {
  return [
    'routes' => [
      RouteGroup ([
        'title'  => '$ADMIN_MENU_TITLE',
        'prefix' => AdminModule::settings ()['prefix'],
        'routes' => AdminModule::routes (),
      ])->activeFor (AdminModule::settings ()['menu']),
    ],
  ];
});
