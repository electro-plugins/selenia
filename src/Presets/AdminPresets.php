<?php
namespace Selene\Modules\Admin\Presets;

use Selene\Matisse\Components\DataGrid;

class AdminPresets
{
  function DataGrid (DataGrid $grid)
  {
    global $controller;
    $grid->attrs ()->apply ([
      'lang'        => $controller->langISO,
      'page_length' => "mem.get ('prefs.rowsPerPage', 10)",
    ]);
  }
}