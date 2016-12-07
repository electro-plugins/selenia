<?php
namespace Selenia\Platform\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Exceptions\ExceptionWithTitle;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Profiles\WebProfile;
use Electro\Routing\Middleware\AutoRoutingMiddleware;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Matisse\Config\MatisseSettings;
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
                  ApplicationMiddlewareInterface $middleware, LocalizationSettings $localizationSettings,
                  NavigationSettings $navigationSettings, ViewEngineSettings $viewEngineSettings)
        use ($moduleInfo, $kernel) {
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

          $middleware->add (AutoRoutingMiddleware::class, null, null, 'router');

          // Check if the platform is correctly installed.
          if ($kernel->devEnv ()) {
            if (!file_exists ("$moduleInfo->path/{$viewEngineSettings->moduleViewsPath()}/platform/layouts/master.html"))
              throw new ExceptionWithTitle ("No theme installed", "Please install a theme.");
          }
        })
      //
      ->onReconfigure (
        function (ApplicationRouterInterface $router) {
          $router->add (Routes::class, 'platform');
        });
  }

}
