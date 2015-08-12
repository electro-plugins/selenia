<?php

use Selene\Modules\Admin\Config\AdminModule;

ModuleOptions (__DIR__, [
  'public'    => 'modules/admin',
  'lang'      => true,
  'templates' => true,
  'views'     => true,
  'presets'   => ['Selene\Modules\Admin\Config\AdminPresets'],
  'config'    => [
    'main'         => [
      'userModel' => 'Selene\Modules\Admin\Models\User',
      'loginView' => 'login.html',
      'translation'    => true,
    ],
    'admin-module' => [
      'prefix'          => 'admin',
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
  'assets'    => [
    "lib/bootstrap/dist/css/bootstrap.min.css",
    "lib/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css",
    "lib/datatables-responsive/css/dataTables.responsive.css",
    "css/metisMenu.css",
    "lib/chosen/chosen.min.css",
    "lib/font-awesome/css/font-awesome.min.css",
    "dist/main.css",
    "lib/jquery/dist/jquery.min.js",
    "lib/chosen/chosen.jquery.min.js",
    "lib/bootstrap/dist/js/bootstrap.min.js",
    "lib/datatables/media/js/jquery.dataTables.js",
    "lib/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.min.js",
    "lib/datatables-responsive/js/dataTables.responsive.js",
    "js/metisMenu.js",
    "js/main.js",
  ],
], function () {
  global $application;
  return [
    'routes' => [
      RouteGroup ([
        'title' => '$ADMIN_MENU_TITLE',
        'prefix' => $application->config['admin-module']['prefix'],
        'routes' => AdminModule::routes (),
      ]),
    ],
  ];
});
