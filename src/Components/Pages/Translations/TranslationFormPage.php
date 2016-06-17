<?php
namespace Selenia\Platform\Components\Pages\Translations;
use Selenia\Platform\Components\AdminPageComponent;
use Selenia\Platform\Models\TranslationData;

class TranslationFormPage extends AdminPageComponent
{
  private $translationData;

  protected function processRequest ()
  {
    $this->processForm ($this->translationData);
  }

  protected function setupModel ()
  {
    $this->translationData = new TranslationData();
    $this->translationData->setLanguages ($this->languages);
    $this->translationData->key = $this->request->getAttribute ('@key');
    $this->translationData->read ();
  }

  protected function setupViewModel ()
  {
    $this->setDataSource ('translation', new DataRecord($this->translationData), true);
  }

}
