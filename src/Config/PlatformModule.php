<?php
namespace Selenia\Platform\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Plugins\Matisse\Config\MatisseSettings;
use Electro\Profiles\WebProfile;
use Electro\Routing\Middleware\AutoRoutingMiddleware;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Selenia\Platform\Components\Widgets\LanguageSelector;
use Selenia\Platform\Config;
use Selenia\Platform\Models\User as UserModel;

class PlatformModule implements ModuleInterface
{
  const ACTION_FIELD = 'selenia-action';
  const PUBLIC_DIR   = 'modules/selenia/platform';

  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
      $kernel
        ->onRegisterServices (
          function (InjectorInterface $injector) {
            $injector
              ->share (PlatformSettings::class);
          })
        //
        ->onConfigure (
          function (MatisseSettings $matisseSettings, AuthenticationSettings $authSettings,
                    KernelSettings $kernelSettings, ApplicationMiddlewareInterface $middleware,
                    LocalizationSettings $localizationSettings,
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

            if ($kernelSettings->isWebBased) {
              $middleware->add (AutoRoutingMiddleware::class, null, null, 'router');
              //$middleware->add (route ('admin/', page ('platform/home.html')), null, 'notFound');
            };
          })
        //
        ->onReconfigure (
          function (ApplicationRouterInterface $router) {
            $router->add (Routes::class, 'platform');
          });
  }

}
