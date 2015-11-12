<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the AdminInterface module.
 *
 * @method $this|string  adminHomeUrl (string $v = null) The relative URL to redirect to when loading the `prefix` URL
 * @method $this|boolean allowDeleteSelf (boolean $v = null) Allow a user to delete him(her)self?
 * @method $this|boolean allowEditRole (boolean $v = null) Allow users to edit their role?
 * @method $this|boolean allowRename (boolean $v = null) Allow users to change their usernames?
 * @method $this|string  defaultRole (string $v = null) The pre-selected role when creating new users
 * @method $this|boolean enableProfile (boolean $v = null) Display a menu item for viewing/editing the logged-in user?
 * @method $this|boolean enableTranslations (boolean $v = null) Enable translations editor?
 * @method $this|boolean enableUserDisabling (boolean $v = null) Support active/inactive user feature?
 * @method $this|string  footer (string $v = null) Sets the footer text displayed on all pages.
 * @method $this|boolean requireAuthentication (boolean $v = null) Enable the authentication middleware for all routes?
 * @method $this|boolean showMenu (boolean $v = null) Display an item for the admin area on the main menu?
 * @method $this|string  urlPrefix (string $v = null) Relative URL that prefixes all URLs to the admin area
 * @method $this|boolean users (boolean $v = null) Enable users management pages?
 */
class AdminInterfaceSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $adminHomeUrl          = 'admin/users';
  private $allowDeleteSelf       = true;
  private $allowEditRole         = true;
  private $allowRename           = true;
  private $defaultRole           = 'standard';
  private $enableProfile         = true;
  private $enableTranslations    = true;
  private $enableUserDisabling   = true;
  private $footer                = '{{ app.appName }} &nbsp;-&nbsp; Copyright &copy; <a href="http://impactwave.com">Impactwave; Lda</a>. All rights reserved.';
  private $requireAuthentication = true;
  private $showMenu              = true;
  private $urlPrefix             = 'admin';
  private $users                 = true;

}
