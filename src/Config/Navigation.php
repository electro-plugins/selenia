<?php
namespace Selenia\Platform\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;

class Navigation implements NavigationProviderInterface
{
  /** @var AuthenticationSettings */
  private $authenticationSettings;
  /** @var SessionInterface */
  private $session;
  /** @var PlatformSettings */
  private $settings;

  public function __construct (PlatformSettings $settings, AuthenticationSettings $authenticationSettings,
                               SessionInterface $session)
  {
    $this->settings               = $settings;
    $this->authenticationSettings = $authenticationSettings;
    $this->session                = $session;
  }

  function defineNavigation (NavigationInterface $nav)
  {
    $userMenu = [
      'languages' => $nav
        ->group()
        ->icon('fa fa-flag')
        ->title('$LANGUAGES')
        ->visible (function () {
          if (!$this->settings->enableTranslations ())
            return false;
          $user = $this->session->user ();
          if (!$user) return false;
          return $user->getFields ()['role'] >= UserInterface::USER_ROLE_ADMIN;
        })
        ->links([
          'enabled' => $nav
            ->link()
            ->id('languages')
            ->icon('fa fa-flag')
            ->title('$LANGUAGES'),
          'translations' => $nav
            ->link()
            ->id('translations')
            ->icon('fa fa-flag')
            ->title('$TRANSLATIONS')
            ->links([
              '@key' => $nav
                ->link()
                ->id('translation')
                ->title('$TRANSLATION')
                ->visibleIfUnavailable(N)
            ]),
        ]),
      'users-management' => $nav
        ->group ()
        ->id ('userMenu')
        ->icon ('fa ion-person')
        ->title ('$APP_USER_MENU')
        ->visible (function () {
          return $this->settings->enableUsersManagement () ;
        })
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
              return $user->getFields ()['role'] >= UserInterface::USER_ROLE_ADMIN;
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
            ->id ('logout')
            ->url ($this->authenticationSettings->getLogoutUrl())
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
            ->links ([
              '' => $nav
                ->link ()
                ->id ('home')
                ->icon ('fa ion-home')
                ->title ('$HOME'),
            ]),
          'settings' => $nav
            ->group ()
            ->id ('settings')
            ->icon ('fa ion-gear-a')
            ->title ('$APP_SETTINGS')
            ->links ($userMenu),
        ]),
    ]);
  }
}
