<?php
namespace Selenia\Plugins\AdminInterface\Components\Translations;
use Selenia\Matisse\DataSet;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;
use Selenia\Plugins\AdminInterface\Models\TranslationData;

class TranslationsPage extends AdminPageComponent
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
