<?php

namespace Selenia\Platform\Components\Pages\Languages;
use Electro\Debugging\Config\DebugSettings;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Localization\Services\Locale;
use Electro\Localization\Services\TranslationService;
use Electro\Plugins\MatisseComponents\Checkbox;
use League\Flysystem\Exception;
use Matisse\Components\Metadata;
use Matisse\Components\Text;
use Matisse\Parser\Expression;
use Matisse\Properties\TypeSystem\type;
use Selenia\Platform\Components\AdminPageComponent;

class LanguagesList extends AdminPageComponent
{
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
                               ModelControllerInterface $modelController, DebugSettings $debugSettings, ModulesRegistry $modulesRegistry, SessionInterface $session, Locale $locale, TranslationService $translationService)
  {
    parent::__construct ($injector, $kernelSettings, $redirection, $navigation, $modelController, $debugSettings);
    $this->session = $session;
    $this->modulesRegistry = $modulesRegistry;
    $this->locale = $locale;
    $this->translationService = $translationService;
  }

  public $template = <<<'HTML'
<Import service="navigation"/>

<AppPage>
  <AssetsGroup>
    <Style src="lib/bootstrap-sweetalert/lib/sweet-alert.css"/>
    <Script src="lib/bootstrap-sweetalert/lib/sweet-alert.min.js"/>
  </AssetsGroup>
  <GridPanel>
      <DataGrid data={modulos} as="i:r" clickable="false" multiSearch column={columns}>
        <Actions>
          <Button 
            icon="ion-checkmark" 
            class="btn-primary ActionSave btSaveInfos" 
            label=$BUTTON_SAVE
          />
        </Actions>
      </DataGrid>
      <Script>
      $(function(){
        $('.btSaveInfos').on('click',function(e){
          swal({
            title: '',
            text: 'Tem a certeza que pretende gravar as alterações?',
            type: 'warning',
            showCancelButton: true
          },
          function() {
            selenia.doAction('submit');
          });
        });
      });
      
      </Script>
  </GridPanel>
</AppPage>
HTML;

  protected function viewModel (ViewModelInterface $viewModel)
  {
    $oModulos = $this->modulesRegistry->onlyPrivate()->getModules();
    $displayModulos = [];
    foreach ($oModulos as $oModulo)
    {
      $iniFiles = $this->translationService->getIniFilesOfModule($oModulo);
      foreach (Locale::$DEFAULTS as $locale)
      {
        $lang = strtoupper($this->locale->shortCode($locale));
        if (in_array("$locale.ini", $iniFiles))
          $displayModulos[$oModulo->name][$lang] = ['name' => $lang,'checked' => true];
        else
          $displayModulos[$oModulo->name][$lang] = ['name' => $lang,'checked' => false];
      }
    }

    $columns = map (Locale::$DEFAULTS, function ($lang)
    {
      $lang = strtoupper($this->locale->shortCode($lang));
      $col = new Metadata(
        $this->getShadowDOM()->context, 'Column', type::metadata, [
          'width' => 100,
          'title' => $lang,
          'align' => 'center',
          'headerAlign' => 'center',
        ]
      );
      $expName = '{i+"_"+r.'.$lang.'.name}';
      $expChecked = '{r.'.$lang.'.checked}';
      $col->addChild(
        Checkbox::create ($col, [],[
          'name' => new Expression($expName),
          'checked' => new Expression($expChecked)
        ])
      );
      return $col;
    });

    $colModulo = new Metadata($this->getShadowDOM()->context,'Column',type::metadata,[
      'width' => '100%',
      'title' => 'Módulo',
    ]);
    $colModulo->addChild(Text::create($colModulo, [], [
      'value' => new Expression ('{i}')
    ]));

    array_unshift($columns,$colModulo);

    $viewModel->set([
      'modulos' => $displayModulos,
      'columns' => $columns
    ]);
    parent::viewModel ($viewModel);
  }

  function action_submit ($param = null)
  {
    $oParsedBody = $this->request->getParsedBody();

    if (isset($oParsedBody['selenia-action']))
      unset($oParsedBody['selenia-action']);

    if (isset($oParsedBody['DataTables_Table_0_length']))
     unset($oParsedBody['DataTables_Table_0_length']);

    foreach ($oParsedBody as $key => $value)
    {
      $a = explode('_',$key);
      if (!$a)
        continue;

      $sModulo = $a[0];
      $sLang = strtolower($a[1]);

      $oModulo = $this->modulesRegistry->getModule($sModulo);

      if (!is_dir("$oModulo->path/resources"))
        mkdir("$oModulo->path/resources");

      $path = $this->translationService->getResourcesLangPath($oModulo);

      if (!is_dir($path))
        mkdir($path);

      $locale = Locale::$LOCALES[Locale::$DEFAULTS[$sLang]];
      $fileName = $locale['name'].".ini";
      $finalPath = "$path/$fileName";

      if (!$value && fileExists($finalPath))
        unlink($finalPath);

      if ($value && !fileExists($finalPath))
        fopen($finalPath, "w");
    }

    $this->session->flashMessage("Alterações gravadas com sucesso!",FlashType::SUCCESS);
  }
}
