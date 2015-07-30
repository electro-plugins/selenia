<?php
namespace Selene\Modules\Admin\Controllers\Translations;
use Selene\Matisse\DataSet;
use Selene\Modules\Admin\Controllers\AdminController;
use Selene\Modules\Admin\Models\TranslationData;

class Translations extends AdminController
{
  const ref = __CLASS__;

  protected function setupViewModel ()
  {
    $model = new TranslationData();
    $model->setLanguages ($this->languages);
    $data = $model->query ();
    $this->setDataSource ('translations', new DataSet($data));
  }

}
