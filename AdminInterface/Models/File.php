<?php
namespace Selenia\Plugins\AdminInterface\Models;

use Selenia\Plugins\IlluminateDatabase\BaseModel;

class File extends BaseModel
{
  public $timestamps = true;

  protected $casts = [
    'metadata' => 'array',
    'image'    => 'boolean',
  ];

  /**
   * Get all of the owning models.
   */
  public function owner ()
  {
    return $this->morphTo ();
  }

}
