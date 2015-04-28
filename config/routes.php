<?php
global $application;
$module    = 'selene-framework/admin-module';
$namespace = 'Selene\Modules\Admin';
$settings  = get ($application->config, 'admin', []);

return [

  RouteGroup ([
    'title'      => '$ADMIN_USERS_MANAGEMENT',
    'icon'       => 'fa fa-user',
    'defaultURI' => 'users',
    'module'     => $module,
    'routes'     => [

      PageRoute ([
        'title'          => '$ADMIN_ADMIN_USERS',
        'URI'            => 'users',
        'model'          => "$namespace\\Models\\User",
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
      ]),
      when (!get ($settings, 'hidePublicUsers'), PageRoute ([
        'title'          => '$ADMIN_WEBSITE_USERS',
        'URI'            => 'site-users',
        'model'          => "$namespace\\Models\\SiteUser",
        'view'           => 'siteUsers/siteUsersIndex',
        'autoController' => true,
        'isIndex'        => true,
        'format'         => 'grid',
        'singular'       => 'utilizador',
        'plural'         => 'Utilizadores',
        'gender'         => 'o',
      ])),
    ]
  ]),

  // This is hidden from the main menu.

  PageRoute ([
    'onMenu'     => false,
    'title'      => '$PROFILE',
    'URI'        => 'user',
    'module'     => $module,
    'model'      => "$namespace\\Models\\User",
    'view'       => "users/adminUserForm.html",
    'controller' => "$namespace\\Controllers\\Users\\AdminUserForm",
    'format'     => 'form',
    'singular'   => 'utilizador',
    'gender'     => 'o',
  ])

];
