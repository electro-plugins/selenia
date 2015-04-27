<?php
namespace Selene\Modules\Admin\Controllers\Translations;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\TranslationData;
use Selene\Matisse\DataSet;

class TranslationsController extends AdminController {

  //--------------------------------------------------------------------------
  protected function setupViewModel() {
    //--------------------------------------------------------------------------
    $model = new TranslationData();
    $model->setLanguages($this->languages);
    $data = $model->query();
    $this->setDataSource('translations', new DataSet($data));
  }

}
