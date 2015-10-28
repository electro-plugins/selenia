<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Localization\Locale;
use Selenia\Plugins\MatisseWidgets\DataGrid;
use Selenia\Plugins\MatisseWidgets\Input;

class AdminPresets
{
  const ref = __CLASS__;
  /**
   * @var Locale
   */
  private $locale;

  function __construct (Locale $locale)
  {
    $this->locale = $locale;
  }


  function DataGrid (DataGrid $grid)
  {
    $grid->attrs ()->apply ([
      'lang'               => $this->locale->name,
      'pageLength'         => "mem.get ('prefs.rowsPerPage', 10)",
      'lengthChangeScript' => "mem.set ('prefs.rowsPerPage', len)",
    ]);
  }

  function Input (Input $input)
  {
    $input->attrs ()->apply ([
      'lang' => $this->locale->name,
    ]);
  }

}
