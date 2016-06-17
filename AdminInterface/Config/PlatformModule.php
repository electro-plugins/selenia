<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Authentication\Config\AuthenticationSettings;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationProviderInterface;
use Selenia\Plugins\AdminInterface\Components;
use Selenia\Plugins\AdminInterface\Components\Pages\Users\UserPage;
use Selenia\Plugins\AdminInterface\Components\Pages\Users\UsersPage;
use Selenia\Plugins\AdminInterface\Components\Widgets\LanguageSelector;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;

class PlatformModule
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, RequestHandlerInterface
{
  const ACTION_FIELD = 'selenia-action';
  /** @var RedirectionInterface */
  private $redirection;
  /** @var RouterInterface */
  private $router;
  /** @var AdminInterfaceSettings */
  private $settings;

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);
    $base = strJoin ($this->settings->urlPrefix (), 'settings...', '/');
    return $this->router
      ->set ([
        $base =>
          [
            when ($this->settings->requireAuthentication (), AuthenticationMiddleware::class),

            when ($this->settings->enableUsersManagement (),
              [
                'users' => factory (function (UsersPage $page) {
                  // This is done here just to show off this possibility
                  $page->templateUrl = 'adminInterface/users/users.html';
                  return $page;
                }),

                'users/@id' => UserPage::class,

                'profile' => factory (function (UserPage $page) {
                  $page->editingSelf = true;
                  return $page;
                }),
              ]),
          ],
      ])
      ->__invoke ($request, $response, $next);
  }

  function configure (ModuleServices $module, AdminInterfaceSettings $settings, RouterInterface $router,
                      RedirectionInterface $redirection, AuthenticationSettings $authSettings)
  {
    $this->settings    = $settings;
    $this->router      = $router;
    $this->redirection = $redirection;
    $authSettings->userModel (UserModel::class);
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideMacros ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::class])
      ->registerComponents ([
        'LanguageSelector' => LanguageSelector::class,
      ])
      ->registerControllersNamespace (Components::class, 'adminInterface')
      ->onPostConfig (function () use ($module) {
        $module
          ->registerRouter ($this)
          ->registerNavigation ($this);
      });
  }

  function defineNavigation (NavigationInterface $navigation)
  {
    $navigation->add ([
       $navigation
        ->group ()
        ->id ('app_home')
        ->title ('$APP_HOME')
         ->url($this->settings->urlPrefix ())
        ->links ([
          'settings' => $navigation
            ->group ()
            ->id ('settings')
            ->icon ('fa fa-user')
            ->title ('$APP_USER_MENU')
            ->visible (N)
            ->links ([
              'profile' => $navigation
                ->link ()
                ->id ('profile')
                ->title ('$LOGIN_PROFILE')
                ->icon ('fa fa-user')
                ->visible ($this->settings->enableProfile ()),
              'users'   => $navigation
                ->link ()
                ->id ('users')
                ->title ('$APP_SETTINGS_USERS')
                ->icon ('fa fa-users')
                ->visible ($this->settings->enableUsersManagement ())
                ->links ([
                  '@id' => $navigation
                    ->link ()
                    ->id ('userForm')
                    ->title ('$APP_SETTINGS_USER')
                    ->visible (N),
                ]),
              '-'       => $navigation->divider (),
              ''        => $navigation
                ->link ()
                ->url ("javascript:selenia.doAction('logout')")
                ->title ('$LOGOUT')
                ->icon ('fa fa-sign-out'),
            ]),
        ]),
    ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector
      ->share (AdminInterfaceSettings::class);
  }

}
