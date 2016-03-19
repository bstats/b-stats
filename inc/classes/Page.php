<?php

/**
 * The Page class contains functions for creating and displaying a page with consistent style.
 */
class Page {

  protected $startTime;
  protected $endTime;

  /**
   * @var string The page's body.
   */
  protected $body;

  /**
   * @var string The page's title.
   */
  protected $title;
  protected $addToHead;
  protected $header;
  protected $footer;
  protected $clearHeader;
  protected $clearFooter;

  /**
   * @var \HtmlElement The navbar.
   */
  protected $navbar;

  public function __construct($title, $body = "") {
    $this->startTime = microtime(true);
    $this->title = $title;
    $this->body = $body;
    $this->user = Site::getUser();

    $this->addToHead = "";
    $this->clearHeader = false;
    $this->clearFooter = false;
    $this->initNavbar();
  }

  private function initNavbar() {
    $this->navbar = div('', 'navbar');
    foreach (Config::getJson('navlinks') as $name => $link) {
      $this->navbar->append(
              a(span("&nbsp;$name&nbsp;", 'navelement'), $link));
    }
    $stylelist = el('ul');
    foreach (Config::getJson("styles") as $name => $code) {
      $stylelist->append(
              el('li', a("&nbsp;$name&nbsp;","javascript:")
                      ->set("onclick","StyleSwitcher.switchTo('$code')")
                      ->set("class",'navelement')->set("title",$name)));
    }
    $this->navbar->append(
            el('ul', el('li', '[Page Style]' . $stylelist, ['class' => 'navelement']), ['class' => 'stylemenu']));
  }

  /**
   * Sets the text in the <code>&lt;title&gt;</code> tags.
   * @param string $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return string The page's title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * For pages that take a while to load.
   * @return string Page's header 
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * Add code to the <code>&lt;head%gt;</code> section of the code
   * @param string $html HTML code to add to the <code>&lt;head%gt;</code> section
   */
  public function addToHead($html) {
    $this->addToHead .= $html;
  }

  /**
   * For pages that take a while to load.
   * @return string Page's footer 
   */
  public function getFooter() {
    return $this->footer;
  }

  /**
   * Returns the body's HTML code.
   * Only includes what's within the
   * <code>&lt;div class='content'%gt;&lt;/div&gt;</code> tags, not including
   * the header.
   * 
   * @return string The body of the page
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Replaces the page's body text.
   * @param string $html The html code to replace 
   */
  public function setBody($html) {
    $this->body = $html;
  }

  /**
   * Adds text to the body. (inside <code>&lt;div class='content'%gt;&lt;/div&gt;</code>)
   * @param string $html HTML to add to the body
   */
  public function appendToBody($html) {
    $this->body .= $html;
  }

  /**
   * @return double Current page execution time
   */
  function getElapsedTime() {
    return round(microtime(true) - $this->startTime, 4);
  }

  function clearHead() {
    $this->clearHeader = true;
  }

  function clearFoot() {
    $this->clearFooter = true;
  }

  /**
   * @return string The navbar.
   */
  protected function renderNavBar() {
    return (string) $this->navbar;
  }

  protected function renderHeader() {
    $this->header = Site::parseHtmlFragment('pagehead.html', [
              '_stylename_', '<!-- pageTitle -->',
              '<!-- additionalHeaders -->', '<!-- navbar -->'], [$this->user->getTheme(), $this->title,
              $this->addToHead, $this->renderNavBar()]);
    return $this->header;
  }

  protected function renderFooter() {
    if (!$this->clearFooter) {
      $this->footer = Site::parseHtmlFragment("pagefoot.html", ["<!--copyright-->"], [file_get_contents("htmls/copyright.html")]);
    } else {
      $this->footer = file_get_contents("htmls/pagefoot.html");
    }
    return $this->footer;
  }

  /**
   * Renders and returns the page.
   * @return string the page's entire HTML
   */
  public function display() {
    $hdr = $this->renderHeader();
    $footer = $this->renderFooter();
    $this->endTime = microtime(true);
    $time = el('p', 
            "page took " . round($this->endTime - $this->startTime, 4) .
            " seconds to execute",
            ['class'=>'pageTime']);
    return $hdr . $this->body . $time . $footer;
  }

}

class FancyPage extends Page {

  /**
   * Access level for the page.
   * 0 = public, 1 = private, 2 = admin, 3 = terrance
   * @var int 
   */
  private $requiredLevel;

  /**
   * Access level of the user.
   * @var int 
   */
  private $userLevel;
  private $board;

  /**
   * Gets the user's current privilege level.
   * @return int
   */
  function checkPrivilege() {
    return $this->user->getPrivilege();
  }

  /**
   * Sets the board name and description (use if the page is board-specific).
   * @param Board $board
   */
  function setBoard($board) {
    if (is_object($board) && $board instanceof Board)
      $this->board = $board;
    else {
      try {
        $this->board = new Board($board);
      } catch (Exception $e) {
        $this->board = null;
      }
    }
  }

  function renderHeader() {
    parent::renderHeader();

    if (!$this->clearHeader) {
      if ($this->board !== null) {
        $boardTitle = "<hr><br>"
                . div("/" . $this->board->getName() . "/ - " . $this->board->getLongName(),'boardTitle')
                . a("View Board on 4chan","//boards.4chan.org/". $this->board->getName())
                        ->set('target','_blank')->set('rel','noreferrer')
                . "<hr>";
      } else {
        $boardTitle = "";
      }
      $this->header .= Site::parseHtmlFragment('pagebody.html', 
              ['<!-- boardTitle -->', '<!-- boardlist -->'], 
              [$boardTitle, Board::getBoardList()]);


      if ($_SERVER['SCRIPT_NAME'] != "/index.php") {
        if ($this->board == null) {
          $this->header .= div('['.a('HOME','/index.php').']','centertext');
        } else {
          $this->header .= "<div style='position:relative; top: -20px;' id='topLinks'>[<a href='/index.php'>Home</a>]";
          if ($_SERVER['SCRIPT_NAME'] != "/board.php")
            $this->header .= " [<a href='/{$this->board->getName()}/'>Return</a>]";
          if ($_SERVER['SCRIPT_NAME'] != "/catalog.php" && $this->board->isSwfBoard() != true)
            $this->header .= " [<a href='/{$this->board->getName()}/catalog'>Catalog</a>]";
          $this->header .= "</div><br>";
        }
      }
    }
    return $this->header;
  }

  /**
   * Default constructor
   * @param string $title Text in the <code>&lt;title&gt;</code> tags.
   * @param string $body Initial body text.
   * @param int $privelege The minimum access level to see the page.
   */
  function __construct($title, $body = "", $privilege = 1, $board = null) {
    parent::__construct($title, $body);

    $this->requiredLevel = $privilege;
    $this->board = $board;
    if ($this->user->getPrivilege() >= Site::LEVEL_SEARCH) {
      $this->addToHead("<script type='text/javascript'>$(document).ready(function(){ImageHover.init('');});</script>");
    }
    if ($this->user->getPrivilege() >= Site::LEVEL_ADMIN) {
      $this->addToHead("<script type='text/javascript' src='/script/bstats-admin.js'></script>");
    }

    if (Model::banned($_SERVER['REMOTE_ADDR'])) {
      $_SESSION['banned'] = true;
      $banInfo = Model::getBanInfo($_SERVER['REMOTE_ADDR']);
      $expires = $banInfo['expires'] == 0 ? "Never" : date("Y-m-d h:i:s T", $banInfo['expires']);
      $this->body = Site::parseHtmlFragment('banned.html', ['__ip__', '__reason__', '__expires__'], [$_SERVER['REMOTE_ADDR'], $banInfo['reason'], $expires]);
      $this->title = "/b/ stats: ACCESS DENIED";
      die($this->display());
    } else {
      $_SESSION['banned'] = false;
    }
    if ($this->user->getPrivilege() < $this->requiredLevel) {
      $this->body = Site::parseHtmlFragment('accessDenied.html', ['__privilege__', '__required__'], [$this->user->getPrivilege(), $this->requiredLevel]);
      $this->title = "/b/ stats: ACCESS DENIED";
      die($this->display());
    }

    $navBarExtra = "";
    if ($this->user->getPrivilege() == 0) { //If not logged in, show login form.
      $navBarExtra .= file_get_contents(Site::getPath() . "/htmls/loginform.html");
    }

    if ($this->user->getPrivilege() > 0) {
      $navBarExtra .= Site::parseHtmlFragment('loginticker.html', 
              ['%username%', '%privilege%', '<!-- more buttons -->'], 
              [$this->user->getUsername(), $this->user->getPrivilege(), $this->renderExtraButtons()]);
    }
    $this->navbar->append($navBarExtra);
  }

  private function renderExtraButtons() {
    $extraButtons = "";
    if ($this->user->getPrivilege() >= Site::LEVEL_ADMIN) {
      $no = Model::getNumberOfReports();
      $reports = $no ? " ($no)" : "";
      $extraButtons .= span('['.a('Reports'.$reports,'/reportQueue.php').']','navelement').PHP_EOL;
    }
    if ($this->user->getPrivilege() > Site::LEVEL_ADMIN) {
      $extraButtons .= span('['.a('SCP','/scp.php').']','navelement').PHP_EOL;
    }
    if ($this->user->canSearch()) {
      $extraButtons .= span('['.a('Search','/advsearch.php').']','navelement').PHP_EOL;
    }
    return $extraButtons;
  }

}
