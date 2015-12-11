<?php
namespace Selenia\Plugins\AdminInterface\Components;

use Selenia\Application;
use Selenia\DataObject;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Plugins\AdminInterface\Config\AdminInterfaceSettings;

class AdminPageComponent extends PageComponent
{
  public $admin;
  /** @var AdminInterfaceSettings */
  public $adminSettings;
  public $baseSubnavURI;
  public $config;
  public $links;
  public $modelInfo;
  public $navigationPath;
  public $sitePage;
  public $subMenu;
  public $subnavURI;

  /** @var NavigationInterface */
  public $navigation;

  function action_delete ($param = null)
  {
    $r = parent::action_delete ($param);
    $this->session->flashMessage ('$ADMIN_MSG_DELETED');
    return $r;
  }

  protected function initialize ()
  {
    if (!$this->session->user ())
      throw new HttpException(403, 'Access denied', 'No user is logged-in' . (
        $this->app->debugMode ? '<br><br>Have you forgotten to setup an authentication middleware?' : ''
        ));
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

  protected function viewModel ()
  {
    parent::viewModel ();
    $this->navigation->request ($this->request);

    return;
    $application = $this->app;
    $model       = $this->model;
//    $pageInfo    = $this->activeRoute;
//    $prefix      = empty($pageInfo->inheritedPrefix) ? '' : "$pageInfo->inheritedPrefix/";
    $path      = $this->navigationPath = $this->getNavigationPath ();
    $pageTitle = $this->getTitle ();
    if (isset($path)) {
      if (!$path || !$path[0][1] || $path[0][1] == '.')
        $navPath = '';
      else {
        $navPath =
          "<li><a href='$application->homeURI'><i class='$application->homeIcon'></i> &nbsp;$application->homeTitle</a></li>";
        for ($i = 0; $i < count ($path); ++$i)
          if (isset($path[$i]))
            $navPath .= '<li><a href="' . $path[$i][1] . '">' . $path[$i][0] . '</a></li>';
      }
    }
    else $navPath = '';
    $admin       = [
      'pageTitle'  => $pageTitle,
      'navPath'    => $navPath,
      //      'subtitle'   => $pageInfo->getSubtitle (),
      'titleField' => property ($this->model, 'titleField'),
      'noItems'    => '$ADMIN_NO_ITEMS ' - property ($this->model, 'plural') . '.',
    ];
    $this->admin = $admin;
//    $this->sitePage = $pageInfo;
    if (isset($model) && $model instanceof DataObject)
      $this->modelInfo = [
        'gender'   => $model->gender,
        'singular' => $model->singular,
        'plural'   => $model->plural,
      ];
//    $route = $pageInfo;
//    $ok   = false;
//    while (isset ($route->parent)) {
//      if ($route->parent instanceof RouteGroup && isset($route->parent->parent)) {
//        $this->subMenu = $route->parent->routes;
//        $this->subnavURI = $route->URI_regexp;
//        if (isset($route->parent->baseSubnavURI)) {
//          if (preg_match ("#{$route->parent->baseSubnavURI}#", $this->URI, $match))
//            $this->baseSubnavURI = $match[0];
//          else throw new ConfigException("No match for baseSubnavURI <b>{$route->parent->baseSubnavURI}</b>.");
//        }
//        $ok = true;
//        break;
//      }
//      $route = $route->parent;
//    };
//    if (!$ok) $this->subMenu = null;
  }

  function inject (AdminInterfaceSettings $settings, NavigationInterface $navigation)
  {
    $this->adminSettings = $settings;
    $this->navigation    = $navigation;
  }

  /**
   * Defines the navigation breadcrumb trail for the current page.
   * Override to define a custom trail for each application page.
   *
   * @return array An array of [pageName,pageURI] arrays which will be displayed
   * as a line of links to those pages.
   * @throws ConfigException
   * @throws FatalException
   * @global Application $application
   */
  protected function getNavigationPath ()
  {
    return;

    $result = [];
    $route  = $this->activeRoute;
    if (isset($route) && isset($route->parent))
      do {
        $URIParams  = $route->getURIParams ();
        $defaultURI = '';
        if ($route instanceof RouteGroup) {
          if (!empty($route->defaultURI))
            $defaultURI = $route->defaultURI;
          else $defaultURI = 'javascript:nop()';
        }
        $link = $defaultURI ?: $route->evalURI ($URIParams);

        if (isset($route->format))
          switch ($route->format) {
            case 'form':
              if (isset($route->model)) {
                list ($dataClass, $modelMethod) = parseMethodRef ($route->getModel ());
                /** @var DataObject $data */
                $data = new $dataClass;
                if (!isset($data))
                  throw new ConfigException ("When generating the navigation path on the URI <b>$route->URI</b>, it was not possible to create an instance of the data class <b>$dataClass</b>.");
                extend ($data, $URIParams);
                $presetParams = $route->getPresetParameters ();
                extend ($data, $presetParams);
              }
              else array_unshift ($result, [$route->getTitle (), $link]);//$this->model ();
              if (isset($data)) {
                if ($data->isNew () && isset($data->gender) && isset($data->singular))
                  array_unshift ($result, ["Nov$data->gender $data->singular", $link]);
                else {
                  $data->read ();
                  $subtitle = $route->getSubtitle ();
                  $title    = $data->getTitle (isset($data->singular)
                    ? ucfirst ($data->singular)
                    :
                    (isset($subtitle) ? $subtitle : $this->getTitle ()));
                  array_unshift ($result, [$title, $link]);
                }
              }

              break;
            case 'grid':
              $subtitle = $route->getSubtitle ();
              array_unshift ($result, [$subtitle, $link]);
              break;
          }

        else array_unshift ($result, [$route->getTitle (), $link]);

        $route = $route->parent;
      } while (isset($route) && isset($route->parent) && isset($route->parent->parent));

    return $result;
  }

}
