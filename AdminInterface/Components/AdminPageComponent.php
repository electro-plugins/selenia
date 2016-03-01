<?php
namespace Selenia\Plugins\AdminInterface\Components;

use Illuminate\Database\Eloquent\Model;
use PhpKit\ConnectionInterface;
use PhpKit\ExtPDO;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\SessionInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;

class AdminPageComponent extends PageComponent
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
    $model = $this->modelController->getModel ();;
    if (!isset($model) || !$model instanceof Model)
      parent::action_submit ();

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

  protected function mergeIntoModel (& $model, array $data = null)
  {
    if (!$data) return;
    if (!$model instanceof Model)
      parent::mergeIntoModel ($model, $data);
    else $model->forceFill (array_normalizeEmptyValues ($data)); //TODO: use fill() instead
  }

  /**
   * Loads the record with the id specified on from the request URI into the model object.
   *
   * If the URI parameter is empty, the model is returned unmodified.
   *
   * @param string $class The model's class name.
   * @param string $param The parameter name. As a convention, it is usually `id`.
   * @return Model The model on success.
   */
  protected function loadRequested ($class, $param = 'id')
  {
    $id = $this->request->getAttribute ("@$param");
    if (!$id) return new $class;
    return $class::findOrFail ($id);
  }


  protected function loadRequestedRecord ($table, $param = 'id')
  {
    $id = $this->request->getAttribute ("@$param");
    if (!$id) return [];
    return $this->sql->query ("SELECT * FROM $table WHERE id=?", [$id])->fetch ();
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
