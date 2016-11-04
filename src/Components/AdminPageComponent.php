<?php
namespace Selenia\Platform\Components;

use Electro\Exceptions\HttpException;
use Electro\Interfaces\SessionInterface;
use Electro\Plugins\IlluminateDatabase\DatabaseAPI;
use Illuminate\Database\Eloquent\Model;
use PhpKit\ExtPDO\ExtPDO;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Selenia\Platform\Components\Base\PageComponent;

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
    $this->saveModel ();
    $this->session->flashMessage ('$APP_MSG_SAVED');
  }

  function inject ()
  {
    return function (ConnectionsInterface $cons, DatabaseAPI $db, SessionInterface $session) {
      $this->db      = $db;
      $this->sql     = $cons->get ()->getPdo ();
      $this->session = $session;
    };
  }

  protected function initialize ()
  {
    $user = $this->session->user ();
    if (!$user)
      throw new HttpException (403, 'Access denied.',
        "No user is logged-in.<p><br>Did you forget to setup an authentication middleware?");
    parent::initialize ();
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
