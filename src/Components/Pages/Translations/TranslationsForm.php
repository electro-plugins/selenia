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
use Selenia\Platform\Components\AdminPageComponent;
use Selenia\Platform\Models\TranslationData;

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
                               ModelControllerInterface $modelController, DebugSettings $debugSettings, ModulesRegistry $modulesRegistry, TranslationService $translationService, Locale $locale, TranslationData $translationData, RedirectionInterface $redirectionInterface)
  {
    parent::__construct ($injector, $kernelSettings, $redirection, $navigation, $modelController, $debugSettings);
    $this->modulesRegistry = $modulesRegistry;
    $this->translationService = $translationService;
    $this->locale = $locale;
    $this->translationData = $translationData;
    $this->redirection = $redirectionInterface;
  }

  public $template = <<<'HTML'
<Import service="navigation"/>
<AppPage>
  <FormPanel>
  
    <FormLayout>
      <input type="hidden" name="key" value="{KeyValue}"/>
      
      <If {isPlugin || !KeyValue}>
        <Field required label="Módulos" name="modulo" bind=modulo>
          <Select emptySelection data={modulos} valueField=id labelField=title autoTag/>
        </Field>
        <Else>
          <Field readOnly labelAfterInput name="modulo" label="Private Módulo" bind=modulo/>
        </Else>
      </If>
      
      <If {KeyValue}>
        <Field readOnly labelAfterInput name="chave" label="Chave" bind=chave required/>
        <Else>
          <Field labelAfterInput name="chave" label="Chave" bind=chave required/>
        </Else>
      </If>
      
      <If {languages}>
        <Field labelAfterInput lang="{language}" languages="{languages}" name="valor" label="Valor" multilang bind=valor/>
        <Else>
          <Field readOnly labelAfterInput name="valor" label="Valor" defaultValue="Escolha um módulo para editar este campo"/>
        </Else>
      </If>
    </FormLayout>
  		
    <Actions>
      <If {canDelete && KeyValue && !isPlugin}>
        <StandardFormActions key="{chave}"/>
        <Else>
          <StandardFormActions/>
        </Else>
      </If>
    </Actions>
    <Script>
    $('input[lang]').first().addClass('active');
    $('input[name="chave"]').keyup(function()
    {
      var Value = $(this).val();
      Value = Value.toUpperCase().replace(/ /g,'_').replace('-','_').replace(/[^\w-]+/g,'');
      $(this).val(Value);
    });
    $('select[name="modulo"]').on('change',function(){
      selenia.doAction('refresh');
    });
    </Script>
  </FormPanel>
</AppPage>
HTML;

  protected $autoRedirectUp = true;

  protected function viewModel (ViewModelInterface $viewModel)
  {
    $oParsedBody = $this->request->getParsedBody();
    $sKey = $this->request->getAttribute('@key');
    $oUser = $this->session->user();

    $data['canDelete'] = $oUser->roleField() == UserInterface::USER_ROLE_DEVELOPER ? true : false;

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

    $data['KeyValue'] = $sKey;
    $data['chave'] = $sKey;
    $data['modulos'] = $displayModulos;
    $data['isPlugin'] = $isPlugin;

    $langsAvailable = [];
    $langsOfKey = $this->translationService->getAvailableLangsOfKey($sKey);
    foreach ($langsOfKey as $langOfKey)
    {
      $langOfKey = strtolower($langOfKey);
      $lang = Locale::$LOCALES[Locale::$DEFAULTS[$langOfKey]];
      $langsAvailable[] = $lang;
      $fieldName = self::valorNameField.$lang['name'];
      $data[$fieldName] = $this->translationService->get($sKey,$lang['name']);
    }

    if (!$isPlugin && $sKey)
    {
      unset($data['modulos']);
      $modulesOfLang = $this->translationService->getAvailableModulesOfKey($sKey);
      $data['modulo'] = $privateModulo ? $privateModulo : $modulesOfLang[0];
    }

    $languagesOfModulo = $this->getAvailableLanguagesOfModulo();
    $data['language'] = $languagesOfModulo ? $languagesOfModulo[0]['name'] : ($langsAvailable ? $langsAvailable[0]['name'] : '');
    $data['languages'] = $languagesOfModulo ? $languagesOfModulo : ($langsAvailable ? $langsAvailable : []);

    if ($oParsedBody)
      $data = array_merge($data,$oParsedBody);

    $viewModel->set($data);
    parent::viewModel ($viewModel);
  }

  private function getAvailableLanguagesOfModulo()
  {
    $oParsedBody = $this->request->getParsedBody();
    $sModulo = get($oParsedBody,'modulo');

    if (!$sModulo)
      return;

    $oModulo = $this->modulesRegistry->getModule($sModulo);
    $langs = [];
    $iniFiles = $this->translationService->getIniFilesOfModule($oModulo);
    foreach ($iniFiles as $iniFile)
    {
      $locale = str_replace('.ini','', $iniFile);
      $langs[] = Locale::$LOCALES[$locale];
    }
    return $langs;
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
    $iniFiles = $this->translationService->getIniFilesOfModule($oModulo);

    $sMsgWord = $sKey ? 'actualizada' : 'criada';

    foreach ($iniFiles as $iniFile)
    {
      $localeName = str_replace('.ini','', $iniFile);
      $langs[] = Locale::$LOCALES[$localeName];

      $fieldName = self::valorNameField.$localeName;
      if (!$sKey) $sKey = $sChave;

      $fieldValue = get($oParsedBody, $fieldName);
      $path = $this->translationService->getResourcesLangPath($oModulo);
      $path = "$path/$iniFile";

      $dataIni = [$sKey => $fieldValue];
      $this->translationData->save($dataIni, $path);
    }

    $this->session->flashMessage ("Chave de Tradução $sMsgWord com sucesso!",FlashType::SUCCESS);
  }

  function action_delete ($param = null)
  {
    $oParsedBody = $this->request->getParsedBody();
    $sKey = get($oParsedBody,'key');
    $sModulo = get($oParsedBody,'modulo');

    if (!$sModulo && $sKey)
      throw new FlashMessageException('Não foi possível eliminar esta chave de tradução!',FlashType::ERROR);

    $oModulo = $this->modulesRegistry->getModule($sModulo);
    $path = $this->translationService->getResourcesLangPath($oModulo);
    $iniFiles = $this->translationService->getIniFilesOfModule($oModulo);

    foreach ($iniFiles as $iniFile)
      $this->translationData->delete($sKey, "$path/$iniFile");

    $this->session->flashMessage ('Chave de Tradução apagada com sucesso!',FlashType::SUCCESS);
    $this->redirection->setRequest($this->request);
    return $this->redirection->to('admin/settings/translations');
  }
}
