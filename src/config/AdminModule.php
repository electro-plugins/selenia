<?php
namespace Selene\Modules\Admin\Config;

class AdminModule
{
  static function settings ()
  {
    global $application;
    return get ($application->config, 'admin-module', []);
  }

  static function routes ()
  {
    global $application;
    $module    = 'selene-framework/admin-module';
    $namespace = 'Selene\Modules\Admin';
    $settings  = self::settings();

    return [

      get ($settings, 'users', true) ?
        PageRoute ([
          'title'          => '$ADMIN_ADMIN_USERS',
          'URI'            => 'users',
          'module'         => $module,
          'model'          => "$application->userModel",
          'view'           => "users/users.html",
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
              'view'           => "users/user.html",
              'controller'     => "$namespace\\Controllers\\Users\\AdminUserForm",
              'autoController' => false,
              'format'         => 'form',
            ])

          ]
        ])
        : null,

      // This is hidden from the main menu.

      get ($settings, 'profile', true) ?
        PageRoute ([
          'onMenu'     => $application->VURI == 'user',
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