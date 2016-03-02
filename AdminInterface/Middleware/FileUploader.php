<?php
namespace Selenia\Plugins\AdminInterface\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;
use Selenia\Plugins\MatisseComponents\Models\File;

class FileUploader implements RequestHandlerInterface
{
  const SOURCE_PATH = 'private/storage/files';
  /**
   * @var Application
   */
  private $app;

  public function __construct (Application $app, DatabaseAPI $db)
  {
    $this->app = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if ($request->getMethod () != 'POST')
      return $response->withStatus (405); // Method not allowed

    $files = $request->getUploadedFiles ();
    if ($files) {
      try {
        /** @var UploadedFileInterface $file */
        $file = array_pop ($files);

        $fileRec             = new File;
        $params              = $request->getQueryParams ();
        $fileRec->owner_id   = get ($params, 'owner_id');
        $fileRec->owner_type = get ($params, 'owner_type');
        $ownerType           = last (explode ('\\', $fileRec->owner_type));
        $uid                 = uniqid ();
        $name                = $file->getClientFilename ();
        $l                   = explode ('.', $name);
        $ext                 = strtolower (array_pop ($l));
        $fileRec->name       = implode ('.', $l);
        $fileRec->path       = self::SOURCE_PATH . "/$ownerType/$fileRec->owner_id/$uid.$ext";
        $fileRec->image      = $ext == 'jpg' || $ext == 'png' || $ext == 'gif' || $ext == 'tiff';
        $fileRec->id         = $uid;
        $fileRec->ext        = $ext;

        $base = $this->app->baseDirectory . '/';
        $path = $base . dirname ($fileRec->path);
        @mkdir ($path, 0777, true);

        $file->moveTo ($base . $fileRec->path);
        $fileRec->save ();

        $response->getBody ()->write ($fileRec->path);
        return $response;
      }
      catch (\Exception $e) {
        echo $e->__toString ();
        exit (500);
      }
    }
    return $response->withStatus (400); // Bad request
  }
}
