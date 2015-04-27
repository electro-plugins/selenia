# Selene Framework Admin Module

> An administration interface for your app.

This module is an integrant part of the Selene Framework.

## Installation

To install this module on your application, using the terminal, `cd` to your app's directory and type:


```shell
composer require selene-framework/admin-module
```

Then, edit your `application.ini.php` and add the module to the `modules` list.

For example:

```php
return [
  'main' => [
    'modules' => [
      'selene-framework/admin-module'
    ]
  ]
];
```

Finally, define a mount point for the module's route map on your app's route map (at `routes.php`).

Choose an URI prefix for the module's URIs (ex: `'admin'`) and create a `RouteGroup` to define it.

For example:

```php
return [
  'routes' => [
  	RouteGroup ([
      'title'      => '$ADMIN',
      'icon'       => 'fa fa-wrench',
      'URI'        => 'admin',       // the URI prefix
      'defaultURI' => 'admin/users', // which page to show when entering the admin interface
      'routes'     => [
        RoutingMap::loadModule ('selene-framework/admin-module'),
      ]
    ])
  ]
];
```

### Multilingual support

The admin module is multilingual, so make sure you have translations enabled on your app's configuration, or create a sub-configuration to enable it for the chosen URI prefix.

## License

The Selene Framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Selene Framework** - Copyright &copy; Impactwave, Lda.
