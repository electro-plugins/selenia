<?php

namespace Selenia\Platform\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Exceptions\ExceptionWithTitle;
use Electro\Exceptions\Fatal\FileNotFoundException;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Profiles\WebProfile;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Matisse\Config\MatisseSettings;
use Selenia\Platform\Components\Widgets\LanguageSelector;
use Selenia\Platform\Config;
use Selenia\Platform\Models\User as UserModel;

class PlatformModule implements ModuleInterface
{
  const ACTION_FIELD  = 'selenia-action';
  const MASTER_LAYOUT = 'platform/layouts/master.html';
  const PUBLIC_DIR    = 'modules/selenia/platform';

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
                  LocalizationSettings $localizationSettings, NavigationSettings $navigationSettings,
                  ViewEngineSettings $viewEngineSettings)
        use ($moduleInfo, $kernel) {
          $localizationSettings->registerTranslations ($moduleInfo);
          $navigationSettings->registerNavigation (Navigation::class);
          $authSettings->userModel (UserModel::class);
          // DO NOT IMPORT THE FOLLOWING NAMESPACE!
          $viewEngineSettings
            ->registerViews ($moduleInfo)
            ->registerViewModelsNamespace (\Selenia\Platform\ViewModels::class);
          $matisseSettings
            ->registerMacros ($moduleInfo)
            ->registerPresets ([Config\PlatformPresets::class])
            ->registerComponents ([
              'LanguageSelector' => LanguageSelector::class,
            ]);
          // DO NOT IMPORT THE FOLLOWING NAMESPACE!
//            ->registerControllersNamespace ($moduleInfo, \Selenia\Platform\Components::class, 'platform');
        })
      //
      ->onReconfigure (
        function (ApplicationRouterInterface $router) {
          $router->add (Routes::class, 'platform');
        });

    // Check if there is a theme installed. Without it, the platform won't work.
    if ($kernel->devEnv ())
      $kernel->onReconfigure (function (ViewServiceInterface $viewService) {
        try {
          $viewService->resolveTemplatePath (self::MASTER_LAYOUT);
        }
        catch (FileNotFoundException $e) {
          throw new ExceptionWithTitle ("No theme installed",
            sprintf ("Please install a theme that provides a <kbd>%s</kbd> template file.", self::MASTER_LAYOUT));
        }
      });
  }

}
