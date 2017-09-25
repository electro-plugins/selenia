<?php

namespace Selenia\Platform\Components\Pages\Translations;
use Electro\Debugging\Config\DebugSettings;
use Electro\Exceptions\FlashMessageException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Localization\Services\Locale;
use Electro\Localization\Services\TranslationService;
use League\Flysystem\Adapter\Local;
use Selenia\Platform\Components\AdminPageComponent;
use Selenia\Platform\Models\TranslationData;
use WriteiniFile\WriteiniFile;

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
   * @var TranslationData
   */
  private $translationData;
  /**
   * @var TranslationService
   */
  private $translationService;

  public function __construct (InjectorInterface $injector, KernelSettings $kernelSettings,
                               RedirectionInterface $redirection, NavigationInterface $navigation,
                               ModelControllerInterface $modelController, DebugSettings $debugSettings, ModulesRegistry $modulesRegistry, TranslationService $translationService, Locale $locale, TranslationData $translationData)
  {
    parent::__construct ($injector, $kernelSettings, $redirection, $navigation, $modelController, $debugSettings);
    $this->modulesRegistry = $modulesRegistry;
    $this->translationService = $translationService;
    $this->locale = $locale;
    $this->translationData = $translationData;
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
            <Else>
              <Field readOnly labelAfterInput name="modulo" label="Private Módulo" bind=modulo/>
            </Else>
  	  </If>
  	  
  	  <If {chave}>
  	    <Field readOnly labelAfterInput name="chave" label="Chave" bind=chave required/>
  	    <Else>
  	      <Field labelAfterInput name="chave" label="Chave" bind=chave required/>
            </Else>
          </If>
          
          <Field labelAfterInput lang="{language}" languages="{languages}" name="valor" label="Valor" multilang bind=valor/>
  	  
  	</FormLayout>
  		
    <Actions>
      <If {canDelete && chave && !isPlugin}>
        <StandardFormActions key="{chave}"/>
        <Else>
          <StandardFormActions/>
        </Else>
      </If>
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

    $privateModulo = "";
    $isPlugin = false;
    $modulesOfKey = $this->translationService->getAvailableModulesOfKey($sKey);
    sort($modulesOfKey);

    foreach ($modulesOfKey as $moduleOfKey)
    {
      if ($this->modulesRegistry->isPrivateModule($moduleOfKey))
      {
        $privateModulo = $moduleOfKey;
        break;
      }

      if ($this->modulesRegistry->isPlugin($moduleOfKey) || $this->modulesRegistry->isSubsystem($moduleOfKey))
      {
        $isPlugin = true;
        break;
      }
    }

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

    $oUser = $this->session->user();

    if ($oUser->roleField() == UserInterface::USER_ROLE_DEVELOPER)
      $data['canDelete'] = true;
    else
      $data['canDelete'] = false;

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

    if (!$isPlugin && $sKey)
    {
      unset($data['modulos']);
      $modulesOfLang = $this->translationService->getAvailableModulesOfKey($sKey);
      $data['modulo'] = $privateModulo ? $privateModulo : $modulesOfLang[0];
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
      throw new FlashMessageException('Os Campos Módulo e Chave são obrigatórios!',FlashType::ERROR);

    $oModulo = $this->modulesRegistry->getModule($sModulo);

    if ($sKey)
      $langsOfKey = $this->translationService->getAvailableLangsOfKey($sKey);
    else
      $langsOfKey = $this->locale->getAvailableExt();

    foreach ($langsOfKey as $langOfKey)
    {
      if ($sKey)
      {
        $langOfKey = strtolower($langOfKey);
        $lang = Locale::$LOCALES[Locale::$DEFAULTS[$langOfKey]];
        $fieldName = self::valorNameField.$lang['name'];
        $langFile = $lang['name'].'.ini';
      }
      else
      {
        $sKey = $sChave;
        $fieldName = self::valorNameField.$langOfKey['name'];
        $langFile = $langOfKey['name'].'.ini';
      }

      $fieldValue = get($oParsedBody, $fieldName);
      $path = $this->translationService->getResourcesLangPath($oModulo);
      $path = "$path/$langFile";

      $dataIni = [$sKey => $fieldValue];
      $this->translationData->save($dataIni, $path);
    }

    $this->session->flashMessage ('Chave de Tradução actualizada com sucesso!');
  }

  function action_delete ($param = null)
  {
    $oParsedBody = $this->request->getParsedBody();
    $sKey = get($oParsedBody,'key');
    $sModulo = get($oParsedBody,'modulo');

    dd($sModulo);
    return parent::action_delete ($param); // TODO: Change the autogenerated stub
  }


}
