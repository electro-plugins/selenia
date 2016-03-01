<?php
namespace Selenia\Plugins\AdminInterface\Services;

use Illuminate\Database\Eloquent\Model;
use Selenia\Database\Services\ModelController as OriginalConroller;
use Selenia\Interfaces\SessionInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;

class ModelController extends OriginalConroller
{
  /**
   * @var DatabaseAPI
   */
  private $db;

  public function __construct (SessionInterface $session, DatabaseAPI $db)
  {
    parent::__construct ($session);
    $this->db = $db;
  }

  function saveModel ()
  {
    if (!$this->model instanceof Model)
      return parent::saveModel ();

    $this->db->connection ()->beginTransaction ();
    try {
      $this->runPipeline ($this->preSavePipeline);
      $s = $this->model->save ();
      $this->runPipeline ($this->postSavePipeline);
      $this->db->connection ()->commit ();
      return $s;
    }
    catch (\Exception $e) {
      $this->db->connection ()->rollBack ();
      throw $e;
    }
  }


}
