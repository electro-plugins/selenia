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
      'loginView' => 'login.html',
    ],
    'admin-module' => [
      'users'           => true,
      'profile'         => true,
      'editRoles'       => true,
      'defaultRole'     => 'standard',
      'activeUsers'     => true,
      'allowDeleteSelf' => true,
      'profilePrefix'   => '',
      'translations'    => true,
      'footer'          => '{{ !application.appName }} &nbsp;-&nbsp; Copyright &copy; <a href="http://impactwave.com">Impactwave, Lda</a>. All rights reserved.',
    ]
  ]
]);
