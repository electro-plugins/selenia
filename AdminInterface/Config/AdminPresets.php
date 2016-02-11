<?php
namespace Selenia\Plugins\AdminInterface\Config;

use Selenia\Localization\Services\Locale;
use Selenia\Plugins\MatisseComponents\DataGrid;
use Selenia\Plugins\MatisseComponents\Field;
use Selenia\Plugins\MatisseComponents\Input;
use Selenia\Plugins\MatisseComponents\Select;
use Selenia\Plugins\MatisseComponents\Switch_;

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
      'lang'               => $this->locale->locale (),
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

  function Field (Field $field)
  {
    $field->props->apply ([
      'languages' => $this->locale->available (),
    ]);
  }

  function Input (Input $input)
  {
    $input->props->apply ([
      'lang' => $this->locale->locale (),
    ]);
  }

  function Select (Select $sel)
  {
    $sel->props->apply ([
      'emptyLabel'    => '$COMPONENT_SELECT_EMPTY_LABEL',
      'noResultsText' => '$COMPONENT_SELECT_NO_RESULTS',
    ]);
  }

  function Switch_ (Switch_ $sw)
  {
    $sw->props->apply ([
      'labelOn'  => '$COMPONENT_SWITCH_LABEL_ON',
      'labelOff' => '$COMPONENT_SWITCH_LABEL_OFF',
      'color'    => 'purple',
    ]);
  }

}
