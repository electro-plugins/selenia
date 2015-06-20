<?php
namespace Selene\Modules\Admin\Config;

use Selene\Matisse\Components\DataGrid;

class AdminPresets
{
  function DataGrid (DataGrid $grid)
  {
    global $controller;
    $grid->attrs ()->apply ([
      'lang'       => $controller->langISO,
      'pageLength' => "mem.get ('prefs.rowsPerPage', 10)",
    ]);
  }
}
