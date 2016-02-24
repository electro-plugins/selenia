<?php
namespace Selenia\Plugins\AdminInterface\Traits;

use Illuminate\Database\Eloquent\Model;
use Selenia\Plugins\AdminInterface\Models\File;

trait FilesTrait
{
  /**
   * Get all the owned files.
   */
  public function files ()
  {
    /** @var Model $this */
    return $this->morphMany (File::class, 'owner');
  }

}
