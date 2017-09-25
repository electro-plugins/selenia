<?php

namespace Selenia\Platform\Components\Pages\Translations;
use Electro\Debugging\Config\DebugSettings;
use Electro\Exceptions\FlashMessageException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Localization\Services\Locale;
use Electro\Localization\Services\TranslationService;
use League\Flysystem\Adapter\Local;
use Selenia\Platform\Components\AdminPageComponent;

class TranslationsForm extends AdminPageComponent
{
  const valorNameField = 'valor_';
  /**
   * @var Locale
   */
  private $locale;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;
  /**
   * @var TranslationService
   */
  private $translationService;

  public function __construct (InjectorInterface $injector, KernelSettings $kernelSettings,
                               RedirectionInterface $redirection, NavigationInterface $navigation,
                               ModelControllerInterface $modelController, DebugSettings $debugSettings, ModulesRegistry $modulesRegistry, TranslationService $translationService, Locale $locale)
  {
    parent::__construct ($injector, $kernelSettings, $redirection, $navigation, $modelController, $debugSettings);
    $this->modulesRegistry = $modulesRegistry;
    $this->translationService = $translationService;
    $this->locale = $locale;
  }

  public $template = <<<'HTML'
<Import service="navigation"/>
<AppPage>
  <FormPanel>
  
  	<FormLayout>
  	  <input type="hidden" name="key" value="{chave}"/>
  	  
  	  <If {isPlugin || !chave}>
            <Field required label="Módulos" name="modulo">
              <Select emptySelection data={modulos} valueField=id labelField=title autoTag/>
            </Field>
  	  </If>
  	  
  	  <If {chave}>
  	    <Field readOnly labelAfterInput name="chave" label="Chave" bind=chave required/>
  	    <Else>
  	      <Field labelAfterInput name="chave" label="Chave" bind=chave required/>
            </Else>
          </If>
          
          <Field labelAfterInput lang="{language}" languages="{languages}" name="valor" label="Valor" multilang bind=valor required/>
  	  
  	</FormLayout>
  		
    <Actions>
      <StandardFormActions/>
    </Actions>
    <Script>
    $('input[lang]').first().addClass('active');
    </Script>
  </FormPanel>
</AppPage>

HTML;

  protected $autoRedirectUp = true;

  protected function viewModel (ViewModelInterface $viewModel)
  {
    $sKey = $this->request->getAttribute('@key');

    $isPlugin = false;
    $modulesOfKey = $this->translationService->getAvailableModulesOfKey($sKey);
    foreach ($modulesOfKey as $moduleOfKey)
      if ($this->modulesRegistry->isPlugin($moduleOfKey) || $this->modulesRegistry->isSubsystem($moduleOfKey))
        $isPlugin = true;

    $displayModulos = [];
    $privateModulos = $this->modulesRegistry->onlyPrivate();
    foreach ($privateModulos->getModules() as $module)
    {
      $path = $this->translationService->getResourcesLangPath($module);
      if (fileExists($path))
        $displayModulos[] = ['id' => $module->name, 'title' => $module->name];
    }

    $data = [
      'chave' => $sKey,
      'modulos' => $displayModulos,
      'isPlugin' => $isPlugin,
    ];

    $langsAvailable = [];
    $langsOfKey = $this->translationService->getAvailableLangsOfKey($sKey);
    foreach ($langsOfKey as $langOfKey)
    {
      $langOfKey = strtolower($langOfKey);
      $lang = $this->locale->locale($langOfKey);
      $langsAvailable[] = Locale::$LOCALES[Locale::$DEFAULTS[$langOfKey]];
      $fieldName = self::valorNameField.$lang->locale();
      $data[$fieldName] = $this->translationService->get($sKey,$lang->locale());
    }

    $data['language'] = $langsAvailable ? $langsAvailable[0]['name'] : $this->locale->locale();
    $data['languages'] = $langsAvailable ? $langsAvailable : $this->locale->getAvailableExt();

    $viewModel->set($data);
    parent::viewModel ($viewModel);
  }

  function action_submit ($param = null)
  {
    $oParsedBody = $this->request->getParsedBody();
    $sKey = get($oParsedBody,'key');
    $sModulo = get($oParsedBody,'modulo');
    $sChave = get($oParsedBody,'chave');

    if (!$sModulo || !$sChave)
      throw new FlashMessageException('Por favor preencha os campos que são obrigatórios',FlashType::ERROR);

    if ($sKey)
    {
      // update
      $langsOfKey = $this->translationService->getAvailableLangsOfKey($sKey);
      foreach ($langsOfKey as $langOfKey)
      {
        $langOfKey = strtolower($langOfKey);
        $lang = Locale::$LOCALES[Locale::$DEFAULTS[$langOfKey]];
        $fieldName = self::valorNameField.$lang['name'];
        $fieldValue = get($oParsedBody, $fieldName);

        $oModulo = $this->modulesRegistry->getModule($sModulo);
        $path = $this->translationService->getResourcesLangPath($oModulo);
        $path = "$path/".$lang['name'].'.ini';

        if (fileExists($path))
        {
          dd($fieldValue);

        }
      }
    }
    else
    {
      // create

    }

//    parent::action_submit ($param); // TODO: Change the autogenerated stub
  }
}
