<?php
namespace Selene\Modules\Admin\Config;

class AdminModule
{

  static function routes ()
  {
    global $application;
    $module    = 'selene-framework/admin-module';
    $namespace = 'Selene\Modules\Admin';
    $settings  = get ($application->config, 'admin', []);

    return [

      get ($settings, 'users') ?
        PageRoute ([
          'title'          => '$ADMIN_ADMIN_USERS',
          'URI'            => 'users',
          'module'         => $module,
          'model'          => "$application->userModel",
          'view'           => "users/usersIndex.html",
          'autoController' => true,
          'isIndex'        => true,
          'format'         => 'grid',
          'singular'       => 'utilizador',
          'plural'         => 'Utilizadores',
          'gender'         => 'o',
          'links'          => [
            'mainForm' => 'users/{username}'
          ],
          'routes'         => [
            SubPageRoute ([
              'URI'            => 'users/{username}',
              'view'           => "users/adminUserForm.html",
              'controller'     => "$namespace\\Controllers\\Users\\AdminUserForm",
              'autoController' => false,
              'format'         => 'form',
            ])

          ]
        ])
        : null,

      // This is hidden from the main menu.

      get ($settings, 'profile') ?
        PageRoute ([
          'onMenu'     => false,
          'title'      => '$LOGIN_PROFILE',
          'URI'        => 'user',
          'module'     => $module,
          'view'       => "users/adminUserForm.html",
          'controller' => "$namespace\\Controllers\\Users\\AdminUserForm",
          'config'     => [
            'self' => true // Editing the logged-in user.
          ]
        ])
        : null,

    ];

  }
}