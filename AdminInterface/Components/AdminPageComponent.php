<?php
namespace Selenia\Plugins\AdminInterface\Components;

use PhpKit\ConnectionInterface;
use PhpKit\ExtPDO;
use Selenia\Application;
use Selenia\DataObject;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;

class AdminPageComponent extends PageComponent
{
  /** @var Application */
  public $admin;
  /** @var AdminInterfaceSettings */
  public $adminSettings;
  /** @var NavigationLinkInterface */
  public $sideMenu;
  /** @var NavigationLinkInterface */
  public $topMenu;
  /** @var ExtPDO */
  protected $pdo;
  /** @var ConnectionInterface */
  protected $connection;

  function action_delete ($param = null)
  {
    $r = parent::action_delete ($param);
    $this->session->flashMessage ('$APP_MSG_DELETED');
    return $r;
  }

  protected function initialize ()
  {
    if (!$this->session->user ())
      throw new HttpException(403, 'Access denied', 'No user is logged-in' . (
        $this->app->debugMode ? '<br><br>Have you forgotten to setup an authentication middleware?' : ''
        ));
    $settings = $this->adminSettings;
    $target              = $settings->topMenuTarget ();
    $this->topMenu       = exists ($target) ? $this->navigation [$target] : $this->navigation;
    $this->sideMenu      = get ($this->navigation->getCurrentTrail ($settings->sideMenuOffset ()), 0);

    parent::initialize ();
  }

  /**
   * @param string $class
   * @return DataObject
   */
  function createModel ($class)
  {
    return new $class ($this->connection);
  }

  function inject ()
  {
    return function (AdminInterfaceSettings $settings, ConnectionInterface $con) {
      $this->adminSettings = $settings;
      $this->connection = $con;
      $this->pdo = $con->getPdo();
    };
  }

  protected function insertData ($model)
  {
    parent::insertData ($model);
    $this->session->flashMessage ('$APP_MSG_SAVED');
  }

  protected function updateData ($model)
  {
    parent::updateData ($model);
    $this->session->flashMessage ('$APP_MSG_SAVED');
  }

}
