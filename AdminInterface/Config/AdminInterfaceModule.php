<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Authentication\Middleware\AuthenticationMiddleware;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationProviderInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Plugins\AdminInterface\Components\Users\UserPage;
use Selenia\Plugins\AdminInterface\Components\Users\UsersPage;
use Selenia\Plugins\AdminInterface\Config;
use Selenia\Plugins\AdminInterface\Models\User as UserModel;

class AdminInterfaceModule
  implements ModuleInterface, ServiceProviderInterface, NavigationProviderInterface, RequestHandlerInterface
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var RouterInterface */
  private $router;
  /** @var AdminInterfaceSettings */
  private $settings;

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);

    return $this->router
      ->set ([
        $this->settings->urlPrefix () . '...' =>
          [
            when ($this->settings->requireAuthentication (), AuthenticationMiddleware::class),

            'GET .' => function () { return $this->redirection->to ($this->settings->adminHomeUrl ()); },

            when ($this->settings->enableUsersManagement (),
              [
                'users' => factory (function (UsersPage $page) {
                  $page->templateUrl = 'users/users.html';
                  $page->preset ([
                    'mainForm' => 'users/{{r.id}}',
                  ]);
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

  function configure (ModuleServices $module, AdminInterfaceSettings $settings, Application $app,
                      RouterInterface $router, RedirectionInterface $redirection)
  {
    $this->settings    = $settings;
    $this->router      = $router;
    $this->redirection = $redirection;
    $app->userModel    = UserModel::class;
    $module
      ->publishPublicDirAs ('modules/selenia-plugins/admin-interface')
      ->provideTranslations ()
      ->provideTemplates ()
      ->provideViews ()
      ->registerPresets ([Config\AdminPresets::class])
      ->registerRouter ($this)
      ->provideNavigation ($this);
  }

  function defineNavigation (NavigationInterface $navigation)
  {
    $navigation->add ([
      $this->settings->urlPrefix () => $navigation
        ->group ()
        ->id ('admin')
        ->icon ('fa fa-cog')
        ->title ('$ADMIN_MENU_TITLE')
        ->visible ($this->settings->showMenu ())
        ->links ([
          'users'   => $navigation
            ->link ()
            ->title ('$ADMIN_ADMIN_USERS')
            ->visible ($this->settings->enableUsersManagement ())
            ->links ([
              '@id' => $navigation
                ->link ()
                ->id ('userForm')
                ->title ('$ADMIN_ADMIN_USER')
                ->visibleIfUnavailable (Y),
            ]),
          'profile' => $navigation
            ->link ()
            ->id ('profile')
            ->title ('$LOGIN_PROFILE')
            ->visible (N),
        ]),
    ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (AdminInterfaceSettings::class);
  }

}
