<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the AdminInterface module.
 *
 * @method $this activeUsers (boolean $v)
 * @method $this allowDeleteSelf (boolean $v)
 * @method $this defaultRole (string $v)
 * @method $this editRoles (boolean $v)
 * @method $this footer (string $v)
 * @method $this menu (boolean $v)
 * @method $this prefix (string $v)
 * @method $this profile (boolean $v)
 * @method $this translations (boolean $v) Enable translations editor.
 * @method $this users (boolean $v)
 *
 * @method boolean getActiveUsers ()
 * @method boolean getAllowDeleteSelf ()
 * @method string  getDefaultRole ()
 * @method boolean getEditRoles ()
 * @method string  getFooter ()
 * @method boolean getMenu ()
 * @method string  getPrefix ()
 * @method boolean getProfile ()
 * @method boolean getTranslations ()
 * @method boolean getUsers ()
 *
 */
class AdminInterfaceSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $activeUsers     = true;
  private $allowDeleteSelf = true;
  private $defaultRole     = 'standard';
  private $editRoles       = true;
  private $footer          = '{{ !application.appName }} &nbsp;-&nbsp; Copyright &copy; <a href="http://impactwave.com">Impactwave; Lda</a>. All rights reserved.';
  private $menu            = true;
  private $prefix          = 'admin';
  private $profile         = true;
  private $translations    = true;
  private $users           = true;

}
