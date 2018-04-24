<?php

namespace Selenia\Platform\ViewModels\Platform\Layouts;

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
  private $platformSettings;
  /** @var NavigationInterface */
  private $navigation;
  /** @var SessionInterface */
  private $session;

  public function __construct (NavigationInterface $navigation, PlatformSettings $adminSettings,
                               SessionInterface $session)
  {
    parent::__construct ();

    $this->platformSettings = $adminSettings;
    $this->session          = $session;
    $this->navigation = $navigation;

    $settings = $this['settings'] = $this->platformSettings;
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
    $this['devMode'] = $user && $user->getFields ()['role'] == UserInterface::USER_ROLE_DEVELOPER;
  }

  function init ()
  {
    // TODO: Implement init() method.
  }
}
