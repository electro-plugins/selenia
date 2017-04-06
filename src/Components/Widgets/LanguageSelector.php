<?php
namespace Selenia\Platform\Components\Widgets;

use Electro\Localization\Services\Locale;
use Matisse\Components\Base\CompositeComponent;

class LanguageSelector extends CompositeComponent
{
  /** @var Locale */
  public $locale;
  public $template = <<<'HTML'
    <Import service="locale"/>
    <ul class="LanguageSelector nav nav-pills bar">
      <For each=i:loc of={locale.getAvailableExt}>
        <Link id={'btn-'+loc.name}
              wrapper= li
              script=  {'selenia.setLang(\''+loc.name+'\')'}
              label=   {loc.label}
              active=  {!i}/>
      </For>
      <li class=disabled><a href=javascript:nop()>$LANGUAGE</a></li>
    </ul>
HTML;

  public function __construct (Locale $locale)
  {
    parent::__construct ();
    $this->locale = $locale;
  }

  protected function init ()
  {
    parent::init ();
    $this->context->getAssetsService ()->addInlineScript (<<<JS
      selenia.on ('languageChanged', function (lang) {
        $ ('.LanguageSelector li').removeClass ('active');
        $ ('#btn-' + lang).addClass ('active');
      }).setLang ('{$this->locale->locale ()}');
JS
      , 'initLanguageSelector');
  }


}
