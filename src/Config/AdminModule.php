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
    $settings  = self::settings ();
    $userModel = $application->userModel ?: 'Selene\Modules\Admin\Model\User';

    return [

      get ($settings, 'users', true) ?
        PageRoute ([
          'title'         => '$ADMIN_ADMIN_USERS',
          'URI'           => 'users',
          'module'        => $module,
          'model'         => $userModel,
          'view'          => "users/users.html",
          'controller'    => "$namespace\\Controllers\\Users\\Users",
          'autoloadModel' => true,
          'isIndex'       => true,
          'format'        => 'grid',
          'links'         => [
            'mainForm' => 'users/{id}'
          ],
          'routes'        => [
            PageRoute ([
              'URI'            => 'users/{id}',
              'view'           => "users/user.html",
              'controller'     => "$namespace\\Controllers\\Users\\User",
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
          'view'       => "users/user.html",
          'controller' => "$namespace\\Controllers\\Users\\User",
          'config'     => [
            'self' => true // Editing the logged-in user.
          ]
        ])
        : null,

    ];

  }
}