<?php
namespace Selenia\Platform\Config;

use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Selenia\Plugins\Login\Config\LoginSettings;

class Navigation implements NavigationProviderInterface
{
  /** @var LoginSettings */
  private $loginSettings;
  /** @var SessionInterface */
  private $session;
  /** @var PlatformSettings */
  private $settings;

  public function __construct (PlatformSettings $settings, LoginSettings $loginSettings, SessionInterface $session)
  {
    $this->settings      = $settings;
    $this->loginSettings = $loginSettings;
    $this->session       = $session;
  }

  function defineNavigation (NavigationInterface $nav)
  {
    $userMenu = [
      'users-management' => $nav
        ->group ()
        ->id ('userMenu')
        ->icon ('fa ion-person')
        ->title ('$APP_USER_MENU')
        ->links ([
          'profile' => $nav
            ->link ()
            ->id ('profile')
            ->title ('$LOGIN_PROFILE')
            ->icon ('fa ion-person')
            ->visible ($this->settings->enableProfile ()),
          'users'   => $nav
            ->link ()
            ->id ('users')
            ->title ('$APP_SETTINGS_USERS')
            ->icon ('fa ion-person-stalker')
            ->visible (function () {
              $user = $this->session->user ();
              if (!$user) return false;
              return $this->settings->enableUsersManagement () && $user->roleField () >= UserInterface::USER_ROLE_ADMIN;
            })
            ->links ([
              '@id' => $nav
                ->link ()
                ->id ('userForm')
                ->title ('$APP_SETTINGS_USER')
                ->visibleIfUnavailable (Y),
            ]),
          '-'       => $nav->divider (),
          ''        => $nav
            ->link ()
            ->url (sprintf ('/%s/logout', $this->loginSettings->urlPrefix ()))
            ->title ('$LOGOUT')
            ->icon ('fa ion-log-out'),
        ]),
    ];

    $nav->add ([
      $nav
        ->group ()
        ->id ('app_home')
        ->title ('$APP_HOME')
        ->icon ('fa fa-home')
        ->url ($this->settings->urlPrefix ())
        ->links ([
          ''         => $nav
            ->group ()
            ->id ('mainMenu')
            ->icon ('fa ion-navicon')
            ->title ('Main Menu')
            ->links ([
              '' => $nav
                ->link ()
                ->id ('home')
                ->icon ('fa ion-home')
                ->title ('Home'),
            ]),
          'settings' => $nav
            ->group ()
            ->id ('settings')
            ->icon ('fa ion-gear-a')
            ->title ('Platform')
            ->links ($userMenu),
        ]),
    ]);
  }

}
