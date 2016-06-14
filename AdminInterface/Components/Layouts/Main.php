<?php
namespace Selenia\Plugins\AdminInterface\Components\Layouts;

use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\Matisse\Components\Base\CompositeComponent;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;
use Selenia\ViewEngine\Lib\ViewModel;

class Main extends CompositeComponent
{
  /** @var AdminInterfaceSettings */
  private $adminSettings;
  /** @var NavigationInterface */
  private $navigation;
  /** @var SessionInterface */
  private $session;

  public function __construct (NavigationInterface $navigation, AdminInterfaceSettings $adminSettings,
                               SessionInterface $session)
  {
    parent::__construct ();

    $this->navigation    = $navigation;
    $this->adminSettings = $adminSettings;
    $this->session       = $session;
  }

  protected function viewModel (ViewModel $viewModel)
  {
    $settings = $viewModel->adminSettings = $this->adminSettings;
    if ($settings->showMenu ()) {
      $target             = $settings->topMenuTarget ();
      $viewModel->topMenu = exists ($target) ? $this->navigation [$target] : $this->navigation;
    }
    $viewModel->sideMenu = get ($this->navigation->getCurrentTrail ($settings->sideMenuOffset ()), 0);

    $user               = $this->session->user ();
    $viewModel->devMode = $user->roleField () == UserInterface::USER_ROLE_DEVELOPER;
  }

}
