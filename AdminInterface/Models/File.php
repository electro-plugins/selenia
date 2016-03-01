<?php
namespace Selenia\Plugins\AdminInterface\Models;

use Selenia\Plugins\IlluminateDatabase\BaseModel;

/**
 * Represents a media file managed by the framework.
 *
 * When creating a new record, the model will automatically compute the `path` and `sort` fields.
 */
class File extends BaseModel
{
  public $incrementing = false;
  public $timestamps   = true;

  protected $casts = [
    'metadata' => 'array',
    'image'    => 'boolean',
  ];

  protected $fillable = [
    'id',
    'name',
    'ext',
    'image',
    'path',
    'metadata',
    'sort',
  ];

  protected static function boot ()
  {
    parent::boot ();

    static::creating (function (self $model) {
      // if it's a class name, convert the namespace to a file path.
      $owner       = str_replace ('\\', '/', $model->owner_type);
      $model->path = "$owner/$model->owner_id/$model->id.$model->ext";

      // Calculate the sorting order for the new file; it will be placed at the end unless specified otherwise.
      if (!isset($model->sort))
        $model->sort =
          $model
            ->query ()
            ->where ('owner_type', $model->owner_type)
            ->where ('owner_id', $model->owner_id)
            ->count ();
    });
  }

  /**
   * Get all of the owning models.
   */
  public function owner ()
  {
    return $this->morphTo ();
  }

}
