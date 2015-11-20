<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\NavigationProviderInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Components\Users\UserPage;
use Selenia\Plugins\AdminInterface\Components\Users\UsersPage;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;
use Selenia\Routing\Navigation;

class AdminInterfaceModule
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, RequestHandlerInterface
{
  /** @var RouterInterface */
  private $router;
  /** @var AdminInterfaceSettings */
  private $settings;

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->router
      ->set ([
        $this->settings->urlPrefix () =>
          [
            when ($this->settings->requireAuthentication (), AuthenticationMiddleware::class),

            'GET:' => function () { return $this->redirection->to ($this->settings->adminHomeUrl ()); },

            when ($this->settings->enableUsersManagement (),
              [
                'users' =>
                  [
                    'GET|POST: users/@id' => factory (function (UsersPage $page) {
                      $page->templateUrl = 'users/users.html';
                      $page->preset ([
                        'mainForm' => 'users/{{r.id}}',
                      ]);
                      return $page;
                    }),

                    '@id' => UserPage::class,
                  ],

                'user' => factory (function (UserPage $page) {
                  $page->editingSelf = true;
                  return $page;
                }),
              ]),
          ],
      ])
      ->__invoke ($request, $response, $next);
  }

  function configure (ModuleServices $module, AdminInterfaceSettings $settings, Application $app,
                      RouterInterface $router)
  {
    $this->settings = $settings;
    $this->router   = $router;
    $app->userModel = UserModel::class;
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideTemplates ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::class])
      ->onPostConfig (function () use ($module, $settings) {
        $module
          ->provideNavigation ($this)
          ->registerRouter ($this);
      });
  }

  function getNavigation ()
  {
    return [
      $this->settings->urlPrefix () => (new Navigation)
        ->title ('$ADMIN_MENU_TITLE')
        ->visible ($this->settings->showMenu ())
        ->next ([
          'users'   => (new Navigation)
            ->title ('$ADMIN_ADMIN_USERS')
            ->visible ($this->settings->enableUsersManagement ())
            ->next ([
              '{userId}' => (new Navigation)
                ->title ('$ADMIN_ADMIN_USER')
                ->visible (N),
            ]),
          'profile' => (new Navigation)
            ->title ('$LOGIN_PROFILE')
            ->visible (N),
        ]),
    ];
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (AdminInterfaceSettings::class);
  }

}
