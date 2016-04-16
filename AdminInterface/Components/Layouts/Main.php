<?php
namespace Selenia\Plugins\AdminInterface\Components\Layouts;

use Selenia\Application;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;

class Main extends CompositeComponent
{
  /** @var AdminInterfaceSettings */
  public $adminSettings;
  /** @var Application */
  public $app;
  /** @var NavigationInterface */
  public $navigation;
  /** @var SessionInterface */
  public $session;
  /** @var NavigationLinkInterface */
  public $sideMenu;
  /** @var NavigationLinkInterface */
  public $topMenu;
  /** @var bool */
  public $devMode;

  public function __construct (Application $app, NavigationInterface $navigation, AdminInterfaceSettings $adminSettings,
                               SessionInterface $session)
  {
    parent::__construct ();

    $this->app           = $app;
    $this->navigation    = $navigation;
    $this->adminSettings = $adminSettings;
    $this->session       = $session;
  }

  protected function viewModel ()
  {
    $this->viewModel = $this;

    $settings = $this->adminSettings;
    if ($settings->showMenu ()) {
      $target        = $settings->topMenuTarget ();
      $this->topMenu = exists ($target) ? $this->navigation [$target] : $this->navigation;
    }
    $this->sideMenu = get ($this->navigation->getCurrentTrail ($settings->sideMenuOffset ()), 0);

    $user = $this->session->user ();
    $this->devMode = $user->roleField () == UserInterface::USER_ROLE_DEVELOPER;
  }

}
