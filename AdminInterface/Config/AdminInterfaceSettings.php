<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the AdminInterface module.
 *
 * @method $this|string  adminHomeUrl (string $v = null)
 * @method $this|boolean activeUsers (boolean $v = null)
 * @method $this|boolean allowDeleteSelf (boolean $v = null)
 * @method $this|boolean allowRename (boolean $v = null)
 * @method $this|string  defaultRole (string $v = null)
 * @method $this|boolean editRoles (boolean $v = null)
 * @method $this|string  footer (string $v = null)
 * @method $this|boolean menu (boolean $v = null)
 * @method $this|string  prefix (string $v = null)
 * @method $this|boolean profile (boolean $v = null)
 * @method $this|boolean translations (boolean $v = null) Enable translations editor.
 * @method $this|boolean users (boolean $v = null)
 */
class AdminInterfaceSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $adminHomeUrl    = 'admin/users';
  private $activeUsers     = true;
  private $allowDeleteSelf = true;
  private $allowRename     = true;
  private $defaultRole     = 'standard';
  private $editRoles       = true;
  private $footer          = '{{ !application.appName }} &nbsp;-&nbsp; Copyright &copy; <a href="http://impactwave.com">Impactwave; Lda</a>. All rights reserved.';
  private $menu            = true;
  private $prefix          = 'admin';
  private $profile         = true;
  private $translations    = true;
  private $users           = true;

}
