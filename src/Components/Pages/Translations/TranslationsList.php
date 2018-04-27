<?php

namespace Selenia\Platform\Components\Pages\Translations;
use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Localization\Services\TranslationService;
use Selenia\Platform\Components\AdminPageComponent;

class TranslationsList extends AdminPageComponent
{
  /**
   * @var TranslationService
   */
  private $translationService;

  public function __construct (InjectorInterface $injector, KernelSettings $kernelSettings,
                               RedirectionInterface $redirection, NavigationInterface $navigation,
                               ModelControllerInterface $modelController, DebugSettings $debugSettings, TranslationService $translationService, SessionInterface $session)
  {
    parent::__construct ($injector, $kernelSettings, $redirection, $navigation, $modelController, $debugSettings);
    $this->translationService = $translationService;
    $this->session = $session;
  }

  public $template = <<<'HTML'
<Import service="navigation"/>

<AppPage>
  <GridPanel>
  
    <If {canCreate}>
    
      <DataGrid data={translations} as="i:r" onClickGoTo={navigation.translation + r.key} multiSearch>
    
        <Column width="25%" title="Key">
          {r.key}
        </Column>
        
        <Column width="25%" title="Value">
          {r.value}
        </Column>
        
        <Column width="25%" title="Module">
          {r.module}
        </Column>
        
        <Column width="25%" title="Locales">
          {r.locale}
        </Column>
        
        <Actions>
           <ButtonNew/>
        </Actions>
        
      </DataGrid>
    
      <Else>
      
        <DataGrid data={translations} as="i:r" onClickGoTo={navigation.translation + r.key} multiSearch>
    
          <Column width="25%" title="Key">
            {r.key}
          </Column>
          
          <Column width="25%" title="Value">
            {r.value}
          </Column>
          
          <Column width="25%" title="Module">
            {r.module}
          </Column>
          
          <Column width="25%" title="Locales">
            {r.locale}
          </Column>
          
          <Actions/>
          
        </DataGrid>
      
      </Else>
    </If>
    
  </GridPanel>
</AppPage>
HTML;

  protected function viewModel (ViewModelInterface $viewModel)
  {
    $oUser = $this->session->user();
    if ($oUser->getFields()['role'] == UserInterface::USER_ROLE_DEVELOPER)
      $data['canCreate'] = true;
    else
      $data['canCreate'] = false;

    $data['translations'] = $this->translationService->getAllTranslations();

    $viewModel->set($data);
    parent::viewModel ($viewModel);
  }

}
