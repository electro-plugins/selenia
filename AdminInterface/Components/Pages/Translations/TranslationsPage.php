<?php
namespace Selenia\Plugins\AdminInterface\Components\Pages\Translations;
use Selenia\Plugins\Matisse\DataSet;
use Selenia\Plugins\AdminInterface\Components\AdminPageComponent;
use Selenia\Plugins\AdminInterface\Models\TranslationData;

class TranslationsPage extends AdminPageComponent
{
  protected function setupViewModel ()
  {
    $model = new TranslationData();
    $model->setLanguages ($this->languages);
    $data = $model->query ();
    $this->setDataSource ('translations', new DataSet($data));
  }

}
