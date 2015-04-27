<?php
namespace Selene\Modules\Admin\Controllers;

class AdminController extends \Controller
{
  public $navigationPath;
  public $subnavURI;
  public $baseSubnavURI;
  public $mainMenu;

  public function setupView ()
  {
    /** @var \Session $session */
    global $session;
    parent::setupView ();
    $this->page->bodyAttrs = ['class' => $session->isValid ? '' : ' login-page'];
    $this->page->extraHeadTags .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
  }


  //--------------------------------------------------------------------------
  protected function getTitle ()
  {
    //--------------------------------------------------------------------------
    return $this->sitePage->getTitle ();
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
  //--------------------------------------------------------------------------
  protected function getNavigationPath ()
  {
    //--------------------------------------------------------------------------
    $result = [];
    $page   = $this->sitePage;
    if (isset($page) && isset($page->parent->parent))
      do {
        $URIParams  = $page->getURIParams ();
        $defaultURI = '';
        if ($page instanceof RouteGroup && !empty($page->defaultURI))
          $defaultURI = empty($page->inheritedPrefix) ? $page->defaultURI : "$page->inheritedPrefix/$page->defaultURI";
        $link = self::modPathOf ($defaultURI ?: $page->evalURI ($URIParams));
        if (isset($page->format))
          switch ($page->format) {
            case 'grid':
              $subtitle = $page->getSubtitle ();
              array_unshift ($result, [$subtitle, $link]);
              break;
            case 'form':
              $moduleInfo = new ModuleInfo($page->getDataModule ());
              if (isset($page->model)) {
                $dataClass = $page->getModel ();
                $data      = newInstanceOf ($dataClass);
                if (!isset($data))
                  throw new ConfigException ("When generating the navigation path on the URI <b>$page->URI</b>, it was not possible to create an instance of the data class <b>$dataClass</b> for the module <b>$moduleInfo->modulePage</b> with URI <b>$moduleInfo->URI</b>.");
                extend ($data, $URIParams);
                $presetParams = $page->getPresetParameters ();
                extend ($data, $presetParams);
                if ($data->isNew () && isset($page->gender) && isset($page->singular))
                  array_unshift ($result, ["Nov$page->gender $page->singular", $link]);
                else {
                  $data->read ();
                  $subtitle = $page->getSubtitle ();
                  $title    = $data->getTitle (isset($page->singular)
                    ? ucfirst ($page->singular)
                    :
                    (isset($subtitle) ? $subtitle : $this->getTitle ()));
                  array_unshift ($result, [$title, $link]);
                }
              }
              break;
          }
        else if (isset($page->parent)) {
          if ($page instanceof RouteGroup && (count ($result) == 0 || $page->title != $result[0][0]))
            array_unshift ($result, [$page->title, $link]);
        }
        else if (isset($page->URL))
          array_unshift ($result, [$page->getSubtitle (), $page->URL]);
        $page = $page->parent;
      } while (isset($page) && isset($page->parent));
    if (count ($result) == 1)
      return [];
    return $result;
  }

  //--------------------------------------------------------------------------
  protected function setupBaseModel ()
  {
    //--------------------------------------------------------------------------
    parent::setupBaseModel ();
    global $application, $model;
    $pageInfo  = $this->sitePage;
    $prefix    = empty($pageInfo->inheritedPrefix) ? '' : "$pageInfo->inheritedPrefix/";
    $path      = $this->navigationPath = $this->getNavigationPath ();
    $pageTitle = $this->getTitle ();
    if (isset($path)) {
      $navPath = count ($path) < 2
        ? ''
        : '<li><a href=""><i class="' . $application->homeIcon . '"></i> &nbsp;' . $application->homeTitle .
          '</a></li>';
      for ($i = 0; $i < count ($path); ++$i)
        if (isset($path[$i]))
          $navPath .= '<li><a href="' . $path[$i][1] . '">' . $path[$i][0] . '</a></li>';
    }
    else $navPath = '';
    $admin = [
      'pageTitle'  => $pageTitle,
      'navPath'    => $navPath,
      'subtitle'   => $pageInfo->getSubtitle (),
      'titleField' => property ($this->dataItem, 'titleField'),
      'noItems'    => 'Não existem {!sitePage.plural}.'
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
    $this->setViewModel ("URI", $URIs);
    $this->setViewModel ("URIParams", $this->URIParams);
    $this->setViewModel ("config", $pageInfo->config);
    $this->setViewModel ("URIParams", $pageInfo->getURIParams ());
    $page = $pageInfo;
    $ok   = false;
    while (isset ($page->parent)) {
      if ($page->parent instanceof SiteGroup && isset($page->parent->parent)) {
        $this->setViewModel ('subMenu', $page->parent->pages);
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

  }

}
