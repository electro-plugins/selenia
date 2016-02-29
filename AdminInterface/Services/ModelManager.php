<?php
namespace Selenia\Plugins\AdminInterface\Services;

use Selenia\Database\Services\ModelManager as OriginalManager;
use Selenia\Interfaces\SessionInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;

class ModelManager extends OriginalManager
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
