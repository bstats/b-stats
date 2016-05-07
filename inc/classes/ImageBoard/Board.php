<?php

namespace ImageBoard;
use ArrayAccess;
use Exception;
use JsonSerializable;
use Model\Model;
use Stats;

class Board implements ArrayAccess, JsonSerializable
{
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
  private $archive_time;
  private $hidden;

  public function __toString()
  {
    return $this->name;
  }

  public function getName():string
  {
    return $this->name;
  }

  public function getLongName():string
  {
    return $this->name_long;
  }

  public function isWorksafe():bool
  {
    return (bool)($this->worksafe);
  }

  public function isSwfBoard():bool
  {
    return (bool)($this->swf_board);
  }

  public function getPages():int
  {
    return $this->pages;
  }

  public function getArchivePages():int
  {
    return (int)ceil($this->getNoThreads() / $this->perpage);
  }

  public function getThreadsPerPage():int
  {
    return $this->perpage;
  }

  public function getMaxActiveThreads():int
  {
    return $this->pages * $this->perpage;
  }

  public function isArchive():bool
  {
    return $this->archive;
  }

  public function getPrivilege():int
  {
    return $this->privilege;
  }

  public function getFirstCrawl():int
  {
    return $this->first_crawl;
  }

  public function getLastCrawl():int
  {
    return $this->last_crawl;
  }

  public function getBoardInfo():array
  {
    return $this->jsonSerialize();
  }

  public function getArchiveTime():int
  {
    return $this->archive_time;
  }

  public function __construct(array $boardInfo)
  {
    $this->name = $boardInfo['shortname'];
    $this->name_long = $boardInfo['longname'];
    $this->worksafe = $boardInfo['worksafe'];
    $this->pages = $boardInfo['pages'];
    $this->perpage = $boardInfo['perpage'];
    $this->swf_board = $boardInfo['swf_board'];
    $this->privilege = $boardInfo['privilege'];
    $this->group = $boardInfo['group'];
    $this->first_crawl = $boardInfo['first_crawl'];
    $this->last_crawl = $boardInfo['last_crawl'];
    $this->archive_time = $boardInfo['archive_time'];
    $this->archive = $boardInfo['is_archive'] == 1;
    $this->hidden = $boardInfo['hidden'] == 1;
  }

  public function getStats():\Stats
  {
    if (!isset($this->stats)) {
      $this->stats = new Stats($this->name);
    }
    return $this->stats;
  }

  public function getThread(int $res):array
  {
    return Model::get()->getThread($this, $res);
  }

  public function getPage(int $no):array
  {
    return Model::get()->getPageOfThreads($this, $no);
  }

  public function getNoThreads():int
  {
    if (isset($this->no_threads)) {
      return $this->no_threads;
    } else {
      try {
        return $this->no_threads = Model::get()->getNumberOfThreads($this);
      } catch (Exception $ex) {
        return 0;
      }
    }
  }

  public function getNoPosts():int
  {
    if (isset($this->no_posts)) {
      return $this->no_posts;
    } else {
      try {
        return $this->no_posts = Model::get()->getNumberOfPosts($this);
      } catch (Exception $ex) {
        return 0;
      }
    }
  }

  public static function getBoardList():string
  {
    $ret = "";
    $boards = Model::get()->getBoards();
    $types = [];
    $types['Archives'] = array_filter($boards, function ($b) {
      return $b->isArchive();
    });
    $types['Boards'] = array_filter($boards, function ($b) {
      return !$b->isArchive();
    });
    foreach ($types as $n => $t) {
      if (count($t) == 0) {
        continue;
      }
      $ret .= $n . ": ";
      $groups = array();
      foreach ($t as $board) {
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
    }
    return $ret;
  }

  /*
   * ArrayAccess implementation
   */

  public function offsetExists($offset)
  {
    return false;
  }

  public function offsetGet($offset)
  {
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

  public function offsetSet($offset, $value)
  {
    return;
  }

  public function offsetUnset($offset)
  {
    return;
  }

  /*
   * JsonSerializable implementation
   */

  public function jsonSerialize():array
  {
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
        'hidden' => $this->hidden,
        'posts' => $this->getNoPosts(),
        'threads' => $this->getNoThreads()
    ];
  }

}
