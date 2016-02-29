<?php
namespace Selenia\Plugins\AdminInterface\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Selenia\Plugins\AdminInterface\Middleware\FileUploader;

/**
 * Hanles the saving of image/file fields on forms.
 *
 * <p>Call this from your controller's onSave(), one call for each form field of image/file type.
 *
 * @property FilesTrait             $model
 * @property ServerRequestInterface $request
 * @method void
 */
trait FileFieldTrait
{
  function saveFileFields (array $names)
  {
    $uploadName = "{$name}_file";
    $files      = $this->request->getUploadedFiles ();

    if (isset($files[$uploadName])) {
      // Upload a new/replacement file.

      /** @var UploadedFileInterface $file */
      $file     = $files[$uploadName];
      $filename = $file->getClientFilename ();
      $n        = explode ('.', $filename);
      $ext      = array_pop ($n);
      $name     = implode ('.', $n);
      $id = uniqid ();
      $type     = $file->getClientMediaType ();
      $isImage  = array_search ($type, [
          'image/jpeg', 'image/png', 'image/gif', 'image/tiff', 'image/bmp',
        ]) !== false;
      $path = FileUploader::SOURCE_PATH . "/";

      $this->model->files ()->create ([
        'id'    => $id,
        'name'  => $name,
        'ext'   => $ext,
        'image' => $isImage,

      ]);
    }
    else if (!isset($this->model->$name)) {
      // Remove an existing file, if it exists.

    }
    // Clear the pseudo-field to prevent it from being saved.
    else unset ($this->model->$name);
  }

}
