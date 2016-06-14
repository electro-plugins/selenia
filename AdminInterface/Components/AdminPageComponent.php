<?php
namespace Selenia\Plugins\AdminInterface\Components;

use Illuminate\Database\Eloquent\Model;
use PhpKit\ConnectionInterface;
use PhpKit\ExtPDO;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\SessionInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;

class AdminPageComponent extends \Selenia\Plugins\Matisse\Components\Base\PageComponent
{
  /**
   * The page's data model.
   *
   * Overriden here to re-declare its type.
   *
   * @var Model
   */
  public $model;
  /** @var SessionInterface */
  public $session;
  /** @var DatabaseAPI */
  protected $db;
  /** @var ExtPDO */
  protected $sql;

  function action_delete ($param = null)
  {
    $model = $this->model;
    if (isset($model) && $model instanceof Model) {
      // Delete multiple records.
      if (isset($param))
        $model->query ()->findOrFail ($param)->delete ();
      // Delete the current record.
      elseif (!is_null ($model->getKey ()))
        $model->delete ();
      // Nothing to do.
      else return;
      $this->session->flashMessage ('$APP_MSG_DELETED');
      return;
    }
    parent::action_delete ();
  }

  function action_submit ($param = null)
  {
    $this->saveModel ();
    $this->session->flashMessage ('$APP_MSG_SAVED');
  }

  protected function initialize ()
  {
    $user = $this->session->user ();
    if (!$user)
      throw new HttpException(403, 'Access denied', 'No user is logged-in');
    parent::initialize ();
  }

  function inject ()
  {
    return function (ConnectionInterface $con, DatabaseAPI $db, SessionInterface $session) {
      $this->db      = $db;
      $this->sql     = $con->getPdo ();
      $this->session = $session;
    };
  }

  /**
   * Save the model on the database.
   *
   * Override if you need to customize the saving process.
   *
   * @throws \Exception
   */
  protected function saveModel ()
  {
    $this->modelController->saveModel ();
  }

}
