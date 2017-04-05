<?php
namespace Selenia\Platform\Config;

use Electro\Localization\Services\Locale;
use Electro\Plugins\MatisseComponents\DataGrid;
use Electro\Plugins\MatisseComponents\Field;
use Electro\Plugins\MatisseComponents\Input;
use Electro\Plugins\MatisseComponents\Select;
use Electro\Plugins\MatisseComponents\Switch_;
use Matisse\Components\Include_;

class PlatformPresets
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
      'rowSelector'        => true,
    ]);
    $grid->setSlot ('noData', [
      Include_::create ($grid, [
        'view' => 'platform/misc/EmptyDataGrid',
        'grid' => $grid, // custom property, accessible on the template via {@grid}
      ]),
    ]);
  }

  function Field (Field $field, array $props = null)
  {
    $field->props->apply ([
      'languages' => $this->locale->getAvailableExt (),
      'lang'      => $this->locale->locale (),
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
      //      'color'    => 'black',
    ]);
  }

}
