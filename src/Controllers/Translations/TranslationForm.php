<?php
namespace Selene\Modules\Admin\Controllers\Translations;
use Selene\Matisse\DataRecord;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\TranslationData;

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
