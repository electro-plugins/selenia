<?php
namespace Selenia\Plugins\AdminInterface\Controllers;

use Selenia\Application;
use Selenia\DataObject;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\FlashType;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Controllers\Controller;
use Selenia\Routing\RouteGroup;

class AdminController extends Controller
{
  public $baseSubnavURI;
  public $mainMenu;
  public $navigationPath;
  public $subnavURI;

  function action_delete ($param = null)
  {
    $r = parent::action_delete ($param);
    $this->setStatus (FlashType::INFO, '$ADMIN_MSG_DELETED');
    return $r;
  }

  protected function initialize ()
  {
    global $session, $application;
    if (!isset($session->user) && $application->requireLogin)
      throw new HttpException(403, 'Access denied', 'No user is logged-in' . (
        $application->debugMode ? '<br><br>Have you forgotten to setup an authentication middleware?' : ''
        ));
    parent::initialize ();
  }

  protected function insertData ()
  {
    parent::insertData ();
    $this->setStatus (FlashType::INFO, '$ADMIN_MSG_SAVED');
  }

  protected function setupBaseViewModel ()
  {
    $session = $this->session;
    parent::setupBaseViewModel ();
    $application = $this->app;
    $model       = $this->model;
    $pageInfo    = $this->activeRoute;
    $prefix      = empty($pageInfo->inheritedPrefix) ? '' : "$pageInfo->inheritedPrefix/";
    $path        = $this->navigationPath = $this->getNavigationPath ();
    $pageTitle   = $this->getTitle ();
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
    $admin = [
      'pageTitle'  => $pageTitle,
      'navPath'    => $navPath,
      'subtitle'   => $pageInfo->getSubtitle (),
      'titleField' => property ($this->model, 'titleField'),
      'noItems'    => '$ADMIN_NO_ITEMS ' - property ($this->model, 'plural') . '.',
    ];
    $this->setViewModel ('admin', $admin);
    $this->setViewModel ('sitePage', $pageInfo);
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
    $this->setViewModel ("sessionInfo", $session);
    if (isset($model) && $model instanceof DataObject)
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
    // Ex: 'selenia-plugins/admin-interface' group becames {{ !selenia-plugins-admin-interface-config }} datasource.
    foreach ($application->config as $k => $v) {
      $this->setViewModel (preg_replace ('/\W/', '-', $k) . '-config', (array)$v);
    }
  }

  protected function updateData ()
  {
    parent::updateData ();
    $this->setStatus (FlashType::INFO, '$ADMIN_MSG_SAVED');
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
        if ($page instanceof RouteGroup) {
          if (!empty($page->defaultURI))
            $defaultURI = $page->defaultURI;
          else $defaultURI = 'javascript:nop()';
        }
        $link = $defaultURI ?: $page->evalURI ($URIParams);

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

        else array_unshift ($result, [$page->getTitle (), $link]);

        $page = $page->parent;
      } while (isset($page) && isset($page->parent) && isset($page->parent->parent));

    return $result;
  }

}
