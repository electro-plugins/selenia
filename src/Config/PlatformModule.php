<?php
namespace Selenia\Platform\Config;

use Electro\Application;
use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\Profiles\WebProfile;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Plugins\Matisse\Config\MatisseSettings;
use Electro\Routing\Middleware\AutoRoutingMiddleware;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Selenia\Platform\Components\Widgets\LanguageSelector;
use Selenia\Platform\Config;
use Selenia\Platform\Models\User as UserModel;
use const Electro\Core\Assembly\Services\CONFIGURE;
use const Electro\Core\Assembly\Services\RECONFIGURE;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

class PlatformModule implements ModuleInterface
{
  const ACTION_FIELD = 'selenia-action';
  const PUBLIC_DIR   = 'modules/selenia/platform';

  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    if ($bootstrapper->profile instanceof WebProfile)
      $bootstrapper
        //
        ->on (REGISTER_SERVICES,
          function (InjectorInterface $injector) {
            $injector
              ->share (PlatformSettings::class);
          })
        //
        ->on (CONFIGURE,
          function (MatisseSettings $matisseSettings, AuthenticationSettings $authSettings, Application $app,
                    ApplicationMiddlewareInterface $middleware, LocalizationSettings $localizationSettings,
                    NavigationSettings $navigationSettings, ViewEngineSettings $viewEngineSettings)
          use ($moduleInfo) {
            $localizationSettings->registerTranslations ($moduleInfo);
            $navigationSettings->registerNavigation (Navigation::class);
            $authSettings->userModel (UserModel::class);
            $viewEngineSettings->registerViews ($moduleInfo);
            $matisseSettings
              ->registerMacros ($moduleInfo)
              ->registerPresets ([Config\PlatformPresets::class])
              ->registerComponents ([
                'LanguageSelector' => LanguageSelector::class,
              ])
              // DO NOT IMPORT THE FOLLOWING NAMESPACE!
              ->registerControllersNamespace ($moduleInfo, \Selenia\Platform\Components::class, 'platform');

            if ($app->isWebBased) {
              $middleware->add (AutoRoutingMiddleware::class, null, null, 'router');
              //$middleware->add (route ('admin/', page ('platform/home.html')), null, 'notFound');
            };
          })
        //
        ->on (RECONFIGURE,
          function (ApplicationRouterInterface $router) {
            $router->add (Routes::class, 'platform');
          });
  }

}
