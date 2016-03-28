<?php

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
      $this->header .= Site::parseHtmlFragment('pagebody.html', 
              ['<!-- boardlist -->','<!-- name -->','<!-- subtitle -->'], 
              [Board::getBoardList(), Config::getCfg('site')['name'], Config::getCfg('site')['subtitle']]);

      if ($_SERVER['SCRIPT_NAME'] != "/index.php") {
        if ($this->board == null) {
          $this->header .= div('['.a('HOME','/index.php').']','centertext');
        } else {
          $this->header .= "<div style='position:relative; top: -20px;' id='topLinks'>[<a href='/index.php'>Home</a>]";
          if ($_SERVER['SCRIPT_NAME'] != "/board.php") {
            $this->header .= " [" . a('Return', "/{$this->board->getName()}/") . "]";
          }
          if ($_SERVER['SCRIPT_NAME'] != "/catalog.php" && !$this->board->isSwfBoard()){
            $this->header .= " [" . a('Catalog', "/{$this->board->getName()}/catalog") . "]";
          }
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

    if ($this->user->getPrivilege() < $this->requiredLevel) {
      throw new PermissionException($this->user->getPrivilege(), $this->requiredLevel);
    }

    $navBarExtra = "";
    if ($this->user->getPrivilege() == 0) { //If not logged in, show login form.
      $navBarExtra .= Site::parseHtmlFragment("loginform.html");
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
      $no = OldModel::getNumberOfReports();
      $reports = $no ? " ($no)" : "";
      $extraButtons .= span('['.a('Reports'.$reports,'/reports').']','navelement').PHP_EOL;
    }
    if ($this->user->getPrivilege() > Site::LEVEL_ADMIN) {
      $extraButtons .= span('['.a('SCP','/scp').']','navelement').PHP_EOL;
    }
    if ($this->user->canSearch()) {
      $extraButtons .= span('['.a('Search','/search').']','navelement').PHP_EOL;
    }
    return $extraButtons;
  }

}
