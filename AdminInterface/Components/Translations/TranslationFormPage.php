<?php
namespace Selenia\Plugins\AdminInterface\Components\Translations;
use Selenia\Matisse\DataRecord;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;
use Selenia\Plugins\AdminInterface\Models\TranslationData;

class TranslationFormPage extends AdminPageComponent
{
  const ref = __CLASS__;

  private $translationData;

  protected function processRequest ()
  {
    $this->processForm ($this->translationData);
  }

  protected function setupModel ()
  {
    $this->translationData = new TranslationData();
    $this->translationData->setLanguages ($this->languages);
    $this->translationData->key = $this->URIParams['key'];
    $this->translationData->read ();
  }

  protected function setupViewModel ()
  {
    $this->setDataSource ('translation', new DataRecord($this->translationData), true);
  }

}
