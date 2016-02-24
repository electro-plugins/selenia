<?php
namespace Selenia\Plugins\AdminInterface\Components;

use Illuminate\Database\Eloquent\Model;
use PhpKit\ConnectionInterface;
use PhpKit\ExtPDO;
use Selenia\Application;
use Selenia\DataObject;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Localization\Services\Locale;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;

class AdminPageComponent extends PageComponent
{
  /** @var Application */
  public $admin;
  /** @var AdminInterfaceSettings */
  public $adminSettings;
  /** @var bool */
  public $devMode;
  /** @var Locale */
  public $locale;
  /**
   * @var Model
   */
  public $model;
  /** @var NavigationLinkInterface */
  public $sideMenu;
  /** @var NavigationLinkInterface */
  public $topMenu;
  /** @var ConnectionInterface */
  protected $connection;
  /** @var DatabaseAPI */
  protected $db;
  /** @var ExtPDO */
  protected $sql;

  function action_delete ($param = null)
  {
    if (!isset($this->model))
      throw new FlashMessageException('Can\'t delete a NULL model.', FlashType::ERROR);
    if (isset($param))
      $this->model->query ()->findOrFail ($param)->delete ();
    elseif (!is_null ($this->model->getKey ()))
      $this->model->delete ();
    else return;
    $this->session->flashMessage ('$APP_MSG_DELETED');
  }

  function action_submit ($param = null)
  {
    $data = $this->model;
    if (isset($data)) {
      if ($data instanceof Model) {
        if ($data->save ())
          $this->session->flashMessage ('$APP_MSG_SAVED');
        return;
      }
    }
    parent::action_submit ();
  }

  protected function initialize ()
  {
    $user = $this->session->user ();
    if (!$user)
      throw new HttpException(403, 'Access denied', 'No user is logged-in' . (
        $this->app->debugMode ? '<br><br>Have you forgotten to setup an authentication middleware?' : ''
        ));
    $this->devMode = $user->roleField () == UserInterface::USER_ROLE_DEVELOPER;
    $settings      = $this->adminSettings;
    if ($settings->showMenu ()) {
      $target        = $settings->topMenuTarget ();
      $this->topMenu = exists ($target) ? $this->navigation [$target] : $this->navigation;
    }
    $this->sideMenu = get ($this->navigation->getCurrentTrail ($settings->sideMenuOffset ()), 0);

    parent::initialize ();
  }

  protected function mergeIntoModel (& $model, array $data = null)
  {
    if (!$data) return;
    if (!$model instanceof Model)
      parent::mergeIntoModel($model, $data);
    else $model->forceFill(array_normalizeEmptyValues ($data)); //TODO: use fill() instead
  }

    function inject ()
  {
    return function (AdminInterfaceSettings $settings, ConnectionInterface $con, Locale $locale, DatabaseAPI $db) {
      $this->adminSettings = $settings;
      $this->connection    = $con;
      $this->db            = $db;
      $this->sql           = $con->getPdo ();
      $this->locale        = $locale;
    };
  }

  /**
   * @param string $class
   * @return DataObject
   */
  function createModel ($class)
  {
    return new $class ($this->connection);
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


}
