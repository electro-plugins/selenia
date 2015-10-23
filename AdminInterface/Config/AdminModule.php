<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Plugins\AdminInterface\Controllers\Users\User;
use Selenia\Plugins\AdminInterface\Controllers\Users\Users;

class AdminModule
{
  const ref = __CLASS__;

  static function routes ()
  {
    global $application;
    $module    = 'selenia-plugins/admin-interface';
    $settings  = self::settings ();
    $userModel = $application->userModel ?: \Selenia\Plugins\AdminInterface\Models\User::ref ();

    return [

      PageRoute ([
        'title'         => '$ADMIN_ADMIN_USERS',
        'URI'           => 'users',
        'module'        => $module,
        'model'         => $userModel,
        'view'          => "users/users.html",
        'controller'    => Users::ref (),
        'autoloadModel' => true,
        'isIndex'       => true,
        'format'        => 'grid',
        'links'         => [
          'mainForm' => 'users/{{id}}',
        ],
        'routes'        => [
          PageRoute ([
            'URI'        => 'users/{id}',
            'view'       => "users/user.html",
            'controller' => User::ref (),
            'format'     => 'form',
          ]),

        ],
      ])->activeFor ($settings->getUsers ()),

      // This is hidden from the main menu.

      PageRoute ([
        'onMenu'     => $application->VURI == 'user',
        'title'      => '$LOGIN_PROFILE',
        'URI'        => 'user',
        'indexURL'   => 'admin',
        'module'     => $module,
        'view'       => "users/user.html",
        'controller' => User::ref (),
        'config'     => [
          'self' => true // Editing the logged-in user.
        ],
      ])->activeFor ($settings->getProfile ()),

      PageRoute ([
        'onMenu'         => false,
        'title'          => '$LOGIN_PROMPT',
        'URI'            => 'login',
        'module'         => $module,
        'view'           => "login.html",
        'autoController' => true,
      ]),

    ];

  }

  /**
   * @return AdminInterfaceConfig
   */
  static function settings ()
  {
    global $application;
    return get ($application->config, 'selenia-plugins/admin-interface');
  }
}
