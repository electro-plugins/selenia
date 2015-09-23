# Admin Interface

> An administration interface or base infrastructure for your app.

This plugin is an integrant, but optional, part of the Selenia framework.

## Features

This plugin provides:

###### Design

1. Administration layouts based on Twitter Bootstrap.
* A main menu for your app.
* Breadcrumb navigation.
* Custom UI components.
* Custom styling for the standard widgets.
* Overridable templates for every bundled page.

###### Users and Authentication

1. User management with roles support.
* A form for editing the logged-in user's profile.
* Automatic login form and logout action.
* A default implementation of the User and Authentication APIs.

###### Translations support

1. A fully translatable interface.
* Translations management.

###### Forms

* Multi-language forms.
* Predefined actions for handling form submissions and automatically creating, updating and deleting records.

#### Bundled UI components

##### Administration / Generic App layouts
 
Tag name  | Description
----------|------------
Admin     | The base layout for all administration pages.
GridPage  | A layout for pages displaying a list of records.
FormPage  | A layout for pages displaying a form.
BaseAdmin | Use this **only** if you need a completely custom design, but retaining all bundled scripts and styles.
Main      | A bare bones layout with the main menu on top.

##### Sub-layouts

Tag name           | Description
-------------------|------------
FormLayout         | A responsive form container. Use `Field` components inside.
FormLayout2Columns | A responsive form container with two columns.

##### Widgets

Tag name           | Description
-------------------|------------
ActionBar          | A bar container for buttons of your page / form actions.
ButtonNew          | A button to create a new record.
ButtonSave         | A button to save the current record.
ButtonsSaveDelete  | Standard buttons to save or delete the current record. The delete button is not displayed for new records.
ButtonSubmit       | A generic submit button.
MenuItem           | A navigation link that is auto-highlighted when it matches the current URL.
Paginator          | A default configuration for the Paginator component.
Panel              | A generic panel, with title bar, footer and actions bar.
Static             | A static (non-editable) form field.
TerminalOutput     | Displays text originating from the output of running command-line processes.
UserMenu           | The default user session menu.

## Installation

To install this plugin on your application, using the terminal, `cd` to your app's directory and type:

```shell
selenia module:install-plugin
```

and select `selenia-plugins/admin-interface` from the displayed list, or type

```shell
selenia module:install-plugin selenia-plugins/admin-interface
```

or type

```shell
composer require selenia-plugins/admin-interface
```

### Required configuration settings

The default bundled administration pages require some settings to be configured.

> If you don't use these pages on your app, you don't need to set these settings.

* `languages =` [ *array of language definitions* ]
* `requireLogin = true`
* `globalSessions` (optional)

The admin interface is multilingual (even if you use just one language on your app), and this plugin enables translation support by default.

> If you have overriden that setting on your app's configuration, you'll need to enable it or create a sub-configuration to enable it for the chosen URI prefix.

You'll need to define, at least, one language on the app's configuration and select a default language on the  `.env` file.

##### Example

On `private/config/application.ini.php`

```php
return [
  'main' => [
    'languages'           => [
      'en:en-US:English:en_US|en_US.UTF-8|us',
      'pt:pt-PT:Português:pt_PT|pt_PT.UTF-8|ptg',
    ],
    'globalSessions'      => false, // share the session between the application and its sub-applications?
    'requireLogin'        => true,  // require login for this application?
  ]
];
```

On `.env`

```
APP_DEFAULT_LANG  = en
```

## Usage

This plugin integrates into your application's main menu. The bundled pages will appear automatically on it.

If the app does not display the menu, you'll need to navigate manually to the provided URLs to see one of the bundled administration pages.

> Relative URL for the user administration page: `admin/users`

If you want pages on your app to inherit the bundled administration graphical layout and default functionality, you'll need to:

1. make your controller classes inherit from `Selenia\Plugins\AdminInterface\Controllers\AdminController`
2. include on each of your views, as root tag, one of the bundled layout templates (ex: `<Admin>`).

> See the bundled administration pages' source code for concrete examples.

## Plugin development

If you need to perform modifications on this plugin's assets, you'll need to rebuild it before commiting those changes.

#### Installing the development tools

```sh
cd private/plugins/selenia-plugins/admin-interface
npm install
bower install
```

#### Rebuilding the plugin

```sh
cd private/plugins/selenia-plugins/admin-interface
npm run build
```

## License

The Selenia framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Selenia framework** - Copyright &copy; 2015 Impactwave, Lda.
