<?php
namespace Selene\Modules\Admin\Controllers\Translations;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\TranslationData;
use Selene\Matisse\DataRecord;

class TranslationForm extends AdminController {

	private $translationData;

  //--------------------------------------------------------------------------
  protected function setupModel() {
  //--------------------------------------------------------------------------
    $this->translationData = new TranslationData();
    $this->translationData->setLanguages($this->languages);
    $this->translationData->key = $this->URIParams['key'];
    $this->translationData->read();
  }

  //--------------------------------------------------------------------------
  protected function setupViewModel() {
  //--------------------------------------------------------------------------
    $this->setDataSource('translation', new DataRecord($this->translationData), TRUE);
  }

  //--------------------------------------------------------------------------
  protected function processRequest() {
  //--------------------------------------------------------------------------
    $this->processForm($this->translationData);
  }

}
