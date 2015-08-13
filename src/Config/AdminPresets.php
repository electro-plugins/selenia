<?php
namespace Selene\Modules\Admin\Config;

use Selene\Matisse\Components\DataGrid;
use Selene\Matisse\Components\Input;

class AdminPresets
{
  function DataGrid (DataGrid $grid)
  {
    global $controller;
    $grid->attrs ()->apply ([
      'lang'               => $controller->langISO,
      'pageLength'         => "mem.get ('prefs.rowsPerPage', 10)",
      'lengthChangeScript' => "mem.set ('prefs.rowsPerPage', len)",
    ]);
  }

  function Input (Input $input)
  {
    global $controller;
    $input->attrs ()->apply ([
      'lang' => $controller->langISO,
    ]);
  }

}
