<?php
namespace Selenia\Platform\Config;

use Electro\Application;
use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Routing\Middleware\AutoRoutingMiddleware;
use Selenia\Platform\Components\Widgets\LanguageSelector;
use Selenia\Platform\Config;
use Selenia\Platform\Models\User as UserModel;

class PlatformModule implements ModuleInterface, ServiceProviderInterface
{
  const ACTION_FIELD = 'selenia-action';
  const PUBLIC_DIR   = 'modules/selenia/platform';

  function boot (Application $app, ApplicationMiddlewareInterface $middleware)
  {
    if ($app->isWebBased) {
      $middleware->add (AutoRoutingMiddleware::class, null, null, 'router');
//      $middleware->add (route ('admin/', page ('platform/home.html')), null, 'notFound');
    }
  }

  function configure (ModuleServices $module, AuthenticationSettings $authSettings)
  {
    $module
      ->provideTranslations ()
      ->provideMacros ()
      ->provideViews ()
      ->registerPresets ([Config\PlatformPresets::class])
      ->registerComponents ([
        'LanguageSelector' => LanguageSelector::class,
      ])
      // DO NOT IMPORT THE FOLLOWING NAMESPACE!
      ->registerControllersNamespace (\Selenia\Platform\Components::class, 'platform')
      ->registerNavigation (Navigation::class)
      ->onPostConfig (function () use ($module) {
        $module->registerRouter (Routes::class, 'platform');
      });;

    $authSettings->userModel (UserModel::class);
  }

  function register (InjectorInterface $injector)
  {
    $injector
      ->share (PlatformSettings::class);
  }

}
