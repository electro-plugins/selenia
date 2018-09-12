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
        ->title('Idiomas')
        ->visible (function () {
          $user = $this->session->user ();
          if (!$user) return false;
          return $user->getFields ()['role'] >= UserInterface::USER_ROLE_STANDARD;
        })
        ->links([
          'translations' => $nav
            ->link()
            ->id('translations')
            ->icon('fa fa-flag')
            ->title('Chaves de Tradução')
            ->links([
              '@key' => $nav
                ->link()
                ->id('translation')
                ->title('Chave de Tradução')
                ->visibleIfUnavailable(N)
            ]),
          'files' => $nav
            ->link()
            ->id('languages')
            ->icon('fa fa-flag')
            ->title('Idiomas')
            ->visible(function () {
              $user = $this->session->user ();
              if (!$user) return false;
              return $user->roleField () == UserInterface::USER_ROLE_DEVELOPER;
             }),
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
