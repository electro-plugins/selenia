<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Localization\Services\Locale;
use Selenia\Plugins\MatisseWidgets\DataGrid;
use Selenia\Plugins\MatisseWidgets\Input;

class AdminPresets
{
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
      'responsive'         => '{
    details: {
      display: $.fn.dataTable.Responsive.display.childRow,
      type: "inline"
    }
  }',
    ]);
  }

  function Input (Input $input)
  {
    $input->attrs ()->apply ([
      'lang' => $this->locale->name,
    ]);
  }

}
