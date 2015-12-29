<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Localization\Services\Locale;
use Selenia\Plugins\MatisseComponents\DataGrid;
use Selenia\Plugins\MatisseComponents\Input;

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
    $grid->props->apply ([
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
    $input->props->apply ([
      'lang' => $this->locale->name,
    ]);
  }

}
