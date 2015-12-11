<?php
namespace Selenia\Plugins\AdminInterface\Components;

use Selenia\Application;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;

class AdminPageComponent extends PageComponent
{
  /** @var Application */
  public $admin;
  /** @var AdminInterfaceSettings */
  public $adminSettings;
  /** @var NavigationInterface */
  public $navigation;

  function action_delete ($param = null)
  {
    $r = parent::action_delete ($param);
    $this->session->flashMessage ('$ADMIN_MSG_DELETED');
    return $r;
  }

  function inject (AdminInterfaceSettings $settings, NavigationInterface $navigation)
  {
    $this->adminSettings = $settings;
    $this->navigation    = $navigation;
  }

  protected function initialize ()
  {
    if (!$this->session->user ())
      throw new HttpException(403, 'Access denied', 'No user is logged-in' . (
        $this->app->debugMode ? '<br><br>Have you forgotten to setup an authentication middleware?' : ''
        ));

    $me = $this->navigation->request ($this->request)->currentLink ();
    if ($me && $parent = $me->parent ())
      $this->indexPage = $parent->url ();

    parent::initialize ();
  }

  protected function insertData ($model)
  {
    parent::insertData ($model);
    $this->session->flashMessage ('$ADMIN_MSG_SAVED');
  }

  protected function updateData ($model)
  {
    parent::updateData ($model);
    $this->session->flashMessage ('$ADMIN_MSG_SAVED');
  }

}
