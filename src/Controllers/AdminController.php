<?php
namespace Selene\Modules\Admin\Controllers;

use Selene\Application;
use Selene\Controller;
use Selene\DataObject;
use Selene\Exceptions\ConfigException;
use Selene\Exceptions\FatalException;
use Selene\Exceptions\Status;
use Selene\Routing\RouteGroup;

class AdminController extends Controller
{
  public $baseSubnavURI;
  public $mainMenu;
  public $navigationPath;
  public $subnavURI;

  function action_delete (DataObject $data = null, $param = null)
  {
    parent::action_delete ($data, $param);
    $this->setStatus (Status::INFO, '$ADMIN_MSG_DELETED');
  }

  protected function insertData (DataObject $data, $param = null)
  {
    parent::insertData ($data, $param);
    $this->setStatus (Status::INFO,'$ADMIN_MSG_SAVED');
  }

  protected function updateData (DataObject $data, $param = null)
  {
    parent::updateData ($data, $param);
    $this->setStatus (Status::INFO, '$ADMIN_MSG_SAVED');
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
    $result = [];
    $page   = $this->activeRoute;
    if (isset($page) && isset($page->parent))
      do {
        $URIParams  = $page->getURIParams ();
        $defaultURI = '';
        if ($page instanceof RouteGroup && !empty($page->defaultURI))
          $defaultURI = $page->defaultURI;
        $link = self::modPathOf ($defaultURI ?: $page->evalURI ($URIParams));

        if (isset($page->format))
          switch ($page->format) {
            case 'form':
              if (isset($page->model)) {
                list ($dataClass, $modelMethod) = parseMethodRef ($page->getModel ());
                /** @var DataObject $data */
                $data = new $dataClass;
                if (!isset($data))
                  throw new ConfigException ("When generating the navigation path on the URI <b>$page->URI</b>, it was not possible to create an instance of the data class <b>$dataClass</b>.");
                extend ($data, $URIParams);
                $presetParams = $page->getPresetParameters ();
                extend ($data, $presetParams);
              }
              else $data = $this->model ();
              if (isset($data)) {
                if ($data->isNew () && isset($data->gender) && isset($data->singular))
                  array_unshift ($result, ["Nov$data->gender $data->singular", $link]);
                else {
                  $data->read ();
                  $subtitle = $page->getSubtitle ();
                  $title    = $data->getTitle (isset($data->singular)
                    ? ucfirst ($data->singular)
                    :
                    (isset($subtitle) ? $subtitle : $this->getTitle ()));
                  array_unshift ($result, [$title, $link]);
                }
              }

              break;
            case 'grid':
              $subtitle = $page->getSubtitle ();
              array_unshift ($result, [$subtitle, $link]);
              break;
          }

        else array_unshift ($result, [$page->getTitle(), $link]);

        $page = $page->parent;
      } while (isset($page) && isset($page->parent));

    return $result;
  }

  protected function setupBaseModel ()
  {
    parent::setupBaseModel ();
    global $application, $model;
    $pageInfo  = $this->activeRoute;
    $prefix    = empty($pageInfo->inheritedPrefix) ? '' : "$pageInfo->inheritedPrefix/";
    $path      = $this->navigationPath = $this->getNavigationPath ();
    $pageTitle = $this->getTitle ();
    if (isset($path)) {
      if (!$path || !$path[0][1] || $path[0][1] == '.')
        $navPath = '';
      else {
        $navPath = "<li><a href='$application->homeURI'><i class='$application->homeIcon'></i> &nbsp;$application->homeTitle</a></li>";
        for ($i = 0; $i < count ($path); ++$i)
          if (isset($path[$i]))
            $navPath .= '<li><a href="' . $path[$i][1] . '">' . $path[$i][0] . '</a></li>';
      }
    }
    else $navPath = '';
    $admin = [
      'pageTitle'  => $pageTitle,
      'navPath'    => $navPath,
      'subtitle'   => $pageInfo->getSubtitle (),
      'titleField' => property ($this->dataItem, 'titleField'),
      'noItems'    => '$ADMIN_NO_ITEMS ' - property ($this->dataItem, 'plural') . '.',
    ];
    $this->setViewModel ('admin', $admin);
    $this->setViewModel ('sitePage', $pageInfo);
    if (isset($pageInfo->model)) {
      $myModel = $model[$pageInfo->model];
      if (isset($myModel->form))
        $this->setViewModel ('formConfig', $myModel->form->config);
    }
    $URIs = [];
    if (isset($pageInfo->links)) {
      foreach ($pageInfo->links as $name => $URI)
        if ($URI[0] == '/')
          $URIs[$name] = $application->baseURI . "/$prefix" . $URI;
        else $URIs[$name] = "$prefix$URI";
    }
    $this->setViewModel ("links", $URIs);
    $this->setViewModel ("URIParams", $this->URIParams);
    $this->setViewModel ("config", $pageInfo->config);
    $this->setViewModel ("URIParams", $pageInfo->getURIParams ());
    if (isset($model))
      $this->setViewModel ("modelInfo", [
        'gender'   => $model->gender,
        'singular' => $model->singular,
        'plural'   => $model->plural,
      ]);
    $page = $pageInfo;
    $ok   = false;
    while (isset ($page->parent)) {
      if ($page->parent instanceof RouteGroup && isset($page->parent->parent)) {
        $this->setViewModel ('subMenu', $page->parent->routes);
        $this->subnavURI = $page->URI_regexp;
        if (isset($page->parent->baseSubnavURI)) {
          if (preg_match ("#{$page->parent->baseSubnavURI}#", $this->URI, $match))
            $this->baseSubnavURI = $match[0];
          else throw new ConfigException("No match for baseSubnavURI <b>{$page->parent->baseSubnavURI}</b>.");
        }
        $ok = true;
        break;
      }
      $page = $page->parent;
    };
    if (!$ok) $this->setViewModel ('subMenu', null);
    // Generate datasources for configuration settings groups.
    // Ex: 'admin-module' group becames {{ !admin-module }} datasource.
    foreach ($application->config as $k => $v) {
      $this->setViewModel ($k, $v);
    }
  }

}
