<?php
namespace Selenia\Platform\Components\Layouts;

use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Matisse\Components\Base\CompositeComponent;
use Selenia\Platform\Config\PlatformSettings;
use Electro\ViewEngine\Lib\ViewModel;

class Main extends CompositeComponent
{
  /** @var PlatformSettings */
  private $adminSettings;
  /** @var NavigationInterface */
  private $navigation;
  /** @var SessionInterface */
  private $session;

  public function __construct (NavigationInterface $navigation, PlatformSettings $adminSettings,
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
      $viewModel->topMenu = exists ($target)
        ? (
          isset($this->navigation [$target]) ? $this->navigation [$target] : null
        )
        : $this->navigation;
    }
    $viewModel->sideMenu = get ($this->navigation->getCurrentTrail ($settings->sideMenuOffset ()), 0);

    $user               = $this->session->user ();
    $viewModel->devMode = $user && $user->roleField () == UserInterface::USER_ROLE_DEVELOPER;
  }

}
