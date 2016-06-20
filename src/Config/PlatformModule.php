<?php
namespace Selenia\Platform\Config;

use Electro\Application;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Middleware\AuthenticationMiddleware;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;
use Selenia\Platform\Components;
use Selenia\Platform\Components\Pages\Users\UserPage;
use Selenia\Platform\Components\Pages\Users\UsersPage;
use Selenia\Platform\Components\Widgets\LanguageSelector;
use Selenia\Platform\Config;
use Selenia\Platform\Middleware\AutoRoutingMiddleware;
use Selenia\Platform\Models\User as UserModel;

class PlatformModule
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, RequestHandlerInterface
{
  const ACTION_FIELD = 'selenia-action';
  const PUBLIC_DIR   = 'modules/selenia/platform';
  /** @var RedirectionInterface */
  private $redirection;
  /** @var RouterInterface */
  private $router;
  /** @var PlatformSettings */
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
                  $page->templateUrl = 'platform/users/users.html';
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

  function boot (Application $app, ApplicationMiddlewareInterface $middleware)
  {
    if ($app->isWebBased)
      $middleware->add (AutoRoutingMiddleware::class, null, 'router');
  }

  function configure (ModuleServices $module, PlatformSettings $settings, RouterInterface $router,
                      RedirectionInterface $redirection, AuthenticationSettings $authSettings)
  {
    $this->settings    = $settings;
    $this->router      = $router;
    $this->redirection = $redirection;
    $authSettings->userModel (UserModel::class);
    $module
      ->publishPublicDirAs (self::PUBLIC_DIR)
      ->provideTranslations ()
      ->provideMacros ()
      ->provideViews ()
      ->registerPresets ([Config\PlatformPresets::class])
      ->registerComponents ([
        'LanguageSelector' => LanguageSelector::class,
      ])
      ->registerControllersNamespace (Components::class, 'platform')
      ->registerRouter ($this)
      ->registerNavigation ($this);
  }

  function defineNavigation (NavigationInterface $navigation)
  {
    $navigation->add ([
      $navigation
        ->group ()
        ->id ('app_home')
        ->title ('$APP_HOME')
        ->url ($this->settings->urlPrefix ())
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
      ->share (PlatformSettings::class);
  }

}
