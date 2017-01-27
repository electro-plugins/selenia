<?php

namespace Selenia\Platform\ViewModels\Layouts;

use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interop\ViewModel;
use Selenia\Platform\Config\PlatformSettings;

/**
 * Provides a view model for the `views/platform/layouts/main.html` view.
 * ><p>**Note:** the template is provided by a theme plugin.
 */
class Main extends ViewModel
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

    $settings = $this['adminSettings'] = $this->adminSettings;
    if ($settings->showMenu ()) {
      $target          = $settings->topMenuTarget ();
      $this['topMenu'] = exists ($target)
        ? (
        isset($this->navigation [$target]) ? $this->navigation [$target] : null
        )
        : $this->navigation;
    }
    $this['sideMenu'] = get ($this->navigation->getCurrentTrail ($settings->sideMenuOffset ()), 0);

    $user            = $this->session->user ();
    $this['devMode'] = $user && $user->roleField () == UserInterface::USER_ROLE_DEVELOPER;
  }

}
