<?php
namespace Selenia\Plugins\AdminInterface\Controllers\Translations;
use Selenia\Matisse\DataSet;
use Selenia\Plugins\AdminInterface\Controllers\AdminController;
use Selenia\Plugins\AdminInterface\Models\TranslationData;

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
