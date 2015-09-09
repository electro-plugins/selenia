<?php
namespace Selenia\Plugins\AdminInterface\Controllers\Translations;
use Selenia\Matisse\DataRecord;
use Selenia\Plugins\AdminInterface\Controllers\AdminController;
use Selenia\Plugins\AdminInterface\Models\TranslationData;

class TranslationForm extends AdminController
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
