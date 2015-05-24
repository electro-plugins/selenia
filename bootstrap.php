<?php

ModuleOptions (__DIR__, [
  'public'    => 'modules/admin',
  'lang'      => true,
  'less'      => 'main.less',
  'templates' => true,
  'views'     => true,
  'config'    => [
    'main'         => [
      'userModel' => 'Selene\Modules\Admin\Models\User',
    ],
    'admin-module' => [
      'users'           => true,
      'profile'         => true,
      'editRoles'       => true,
      'defaultRole'     => 'standard',
      'activeUsers'     => true,
      'allowDeleteSelf' => true,
      'profilePrefix'   => '',
      'translations'    => true
    ]
  ]
]);
