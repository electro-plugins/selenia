<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\MiddlewareInterface;
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
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, MiddlewareInterface
{
  /** @var AdminInterfaceSettings */
  private $settings;



  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $router = $this->router;
    $settings = $this->settings;
    return $router
      ->for ($request, $response, $next)
      ->match ([
        $settings->urlPrefix () => [
          $settings->requireAuthentication () ? AuthenticationMiddleware::class : null,
          'GET .' => $this->redirection->to ($settings->adminHomeUrl ()),
          'users' => UsersPage::class,
          'user'  => function (UserPage $page) {
            $page->editingSelf = true;
            return $page;
          },
        ],
      ]);

    $router = $this->router;
    return $router
      ->set ($request, $response, $next)
      ->matchPrefix ($this->settings->urlPrefix ()) ?
      (
      $router
        ->on ('GET', function () {
          return $this->redirection->to ($this->settings->adminHomeUrl ());
        })
        ?: ($this->settings->requireAuthentication () ? AuthenticationMiddleware::class : null)
        ?: $router->branch ([
          'users' => UsersPage::class,
          'user'  => make (
            function (UserPage $page) {
              $page->editingSelf = true;
              return $page;
            }),
        ])
      ) : false;


    return $this->router
      ->set ($request, $response, $next)
      ->run ([
        matchPrefix ($this->settings->urlPrefix (), [
          matchThis ('GET', function () {
            return $this->redirection->to ($this->settings->adminHomeUrl ());
          }),
          $this->settings->requireAuthentication () ? AuthenticationMiddleware::class : null,
          branch ([
            'users' => UsersPage::class,
            'user'  => make (
              function (UserPage $page) {
                $page->editingSelf = true;
                return $page;
              }),
          ]),
        ]),
      ]);
  }

  function __invoke2 (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $this->router
      ->set ($request, $response, $next)
      ->run ([
        (new MatchPrefixRouter ($this->settings->urlPrefix (), [
          (new MatchThisRouter ('GET', function () {
            return $this->redirection->to ($this->settings->adminHomeUrl ());
          })),
          $this->settings->requireAuthentication () ? AuthenticationMiddleware::class : null,
          (new BranchRouter ([
            'users' => UsersPage::class,
            'user'  => make (
              function (UserPage $page) {
                $page->editingSelf = true;
                return $page;
              }),
          ])),
        ])),
      ]);
  }

  function __invoke3 (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $this->router
      ->set ($request, $response, $next)
      ->matchPrefix ($this->settings->urlPrefix (),
        function ($request) {
          return $this->router
            ->set ($request)
            ->on ('GET')
            ? $router->redirection ()->to ($this->settings->adminHomeUrl ())
            : $this->router
              ->middleware ($this->settings->requireAuthentication () ? AuthenticationMiddleware::class : null)
              ->dispatch ([
                'users' => UsersPage::class,
                'user'  => [
                  function (UserPage $page) {
                    $page->editingSelf = true;
                    return $page;
                  },
                ],
              ])
              ->go ();
        })->go ();
  }

  function configure (ModuleServices $module, AdminInterfaceSettings $settings, Application $app)
  {
    $this->settings = $settings;
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

  /**
   * Registers services on the provided dependency injector.
   * > **Best practice:** do not use the injector to fetch dependencies here.
   * @param InjectorInterface $injector
   */
  function register (InjectorInterface $injector)
  {
    $injector->share (AdminInterfaceSettings::class);
  }

}
