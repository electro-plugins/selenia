<?php
namespace Selenia\Platform\Components\Pages\Translations;
use Electro\Plugins\Matisse\DataSet;
use Selenia\Platform\Components\AdminPageComponent;
use Selenia\Platform\Models\TranslationData;

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
