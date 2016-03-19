<?php

class Board implements ArrayAccess, JsonSerializable {

  private $name;
  private $name_long;
  private $worksafe;
  private $pages;
  private $perpage;
  private $swf_board;
  private $archive;
  private $privilege;
  private $first_crawl;
  private $last_crawl;
  private $no_threads;
  private $no_posts;

  public function __toString() {
    return $this->name;
  }

  public function getName() {
    return $this->name;
  }

  public function getLongName() {
    return $this->name_long;
  }

  public function isWorksafe() {
    return (boolean) ($this->worksafe);
  }

  public function isSwfBoard() {
    return (boolean) ($this->swf_board);
  }

  public function getPages() {
    return $this->pages;
  }

  public function getThreadsPerPage() {
    return $this->perpage;
  }

  public function getMaxActiveThreads() {
    return $this->pages * $this->perpage;
  }

  public function isArchive() {
    return $this->archive;
  }

  public function getPrivilege() {
    return $this->privilege;
  }

  public function getFirstCrawl() {
    return $this->first_crawl;
  }

  public function getLastCrawl() {
    return $this->last_crawl;
  }

  public function getBoardInfo() {
    return $this->jsonSerialize();
  }

  public function __construct($shortname) {
    $boardInfo = Model::getBoardInfo($shortname);
    if ($boardInfo == false || $boardInfo['shortname'] !== $shortname) {
      throw new Exception("Board does not exist");
    }
    $this->name = $shortname;
    $this->name_long = $boardInfo['longname'];
    $this->worksafe = $boardInfo['worksafe'];
    $this->pages = $boardInfo['pages'];
    $this->perpage = $boardInfo['perpage'];
    $this->swf_board = $shortname === 'f' ? true : false;
    $this->privilege = $boardInfo['privilege'];
    $this->group = $boardInfo['group'];
    $this->first_crawl = $boardInfo['first_crawl'];
    $this->last_crawl = $boardInfo['last_crawl'];
    $this->archive = true; //no `real` boards here. maybe in the future
  }

  public function getStats() {
    if (!isset($this->stats)) {
      $this->stats = new Stats($this->name);
    }
    return $this->stats;
  }

  public function getThread($res, $deleted = false) {
    return Model::getThread($this->name, $res, $deleted);
  }

  public function getPage($no) {
    return Model::getPage($this, $no);
  }

  public function getNoThreads() {
    if (isset($this->no_threads)) {
      return $this->no_threads;
    } else {
      return $this->no_threads = Model::getNumberOfThreads($this->name);
    }
  }

  public function getNoPosts() {
    if (isset($this->no_posts)) {
      return $this->no_posts;
    } else {
      return $this->no_posts = Model::getNumberOfPosts($this->name);
    }
  }

  public static function getAllBoards() {
    $boards = Model::getBoards();
    foreach ($boards as $boardinfo) {
      $ret[] = new Board($boardinfo['shortname']);
    }
    return $ret;
  }

  public static function getBoardList() {
    $ret = "";
    $boards = Model::getBoards();
    $groups = array();
    foreach ($boards as $board) {
      $groups[$board['group']][] = $board;
    }
    foreach ($groups as $group) {
      $ret .= "[";
      $i = 0;
      foreach ($group as $board) {
        if ($i++ > 0) {
          $ret .= " / ";
        }
        $ret .= '<a href="/' . $board['shortname'] . '/" title="' . $board['longname'] . '">' . $board['shortname'] . '</a>';
      }
      $ret .= "] ";
    }
    return $ret;
  }

  /*
   * ArrayAccess implementation
   */

  public function offsetExists($offset) {
    return false;
  }

  public function offsetGet($offset) {
    switch ($offset) {
      case "shortname":
        return $this->name;
      case "longname":
        return $this->name_long;
      case "first_crawl":
        return $this->first_crawl;
      case "last_crawl":
        return $this->last_crawl;
      case "pages":
        return $this->pages;
      case "perpage":
        return $this->perpage;
      case "privilege":
        return $this->privilege;
      case "worksafe":
        return $this->worksafe;
      case "swf_board":
        return $this->swf_board;
      case "is_archive":
        return $this->archive;
      case "group":
        return $this->group;
    }
    return null;
  }

  public function offsetSet($offset, $value) {
    return;
  }

  public function offsetUnset($offset) {
    return;
  }

  /*
   * JsonSerializable implementation
   */

  public function jsonSerialize() {
    return [
      'shortname' => $this->name,
      'longname' => $this->name_long,
      'first_crawl' => $this->first_crawl,
      'last_crawl' => $this->last_crawl,
      'pages' => $this->pages,
      'perpage' => $this->perpage,
      'privilege' => $this->privilege,
      'worksafe' => $this->worksafe,
      'swf_board' => $this->swf_board,
      'is_archive' => $this->archive,
      'group' => $this->group,
      'posts' => $this->getNoPosts(),
      'threads' => $this->getNoThreads()
    ];
  }

}
