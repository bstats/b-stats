<?php

namespace Model;

use Site\Config;
use Exception;
use ImageBoard;
use ImageBoard\Board;
use InvalidArgumentException;
use NotFoundException;
use PDO;
use PDOStatement;
use ImageBoard\Post;
use Model\PostSearchResult;
use Site\Site;
use ImageBoard\Thread;
use Site\User;

class Model implements \Model\IModel
{

  /** @var Model instance */
  private static $instance;

  public static function get(): Model
  {
    if (isset(self::$instance))
      return self::$instance;
    self::$instance = new Model();
    return self::$instance;
  }

  /** @var PDO */
  private $conn_rw;

  /** @var PDO */
  private $conn_ro;

  private function __construct()
  {
    $this->conn_ro = Config::getPDOConnection();
    $this->conn_rw = Config::getPDOConnectionRW();
  }

  // Board functions

  /**
   * Get all the boards in this DB.
   * @param bool $showHidden Show hidden boards?
   * @return Board[] array of all boards in this database.
   */
  function getBoards(bool $showHidden = false): array
  {
    $clause = $showHidden ? "" : "WHERE `hidden`=0";
    $b = $this->conn_ro
        ->query("SELECT * FROM `boards` $clause ORDER BY `group` ASC, `shortname` ASC")
        ->fetchAll();
    $ret = [];
    foreach ($b as $boardinfo) {
      $ret[$boardinfo['shortname']] = new ImageBoard\Board($boardinfo);
    }
    return $ret;
  }

  /**
   *
   * @param string $shortname
   * @param string $longname
   * @param bool $worksafe
   * @param int $pages
   * @param int $per_page
   * @param int $privilege
   * @param bool $swf_board
   * @param int $group
   * @param bool $hidden
   */
  function addBoard(string $shortname, string $longname, int $worksafe,
                    int $pages, int $per_page, int $privilege, int $swf_board,
                    int $group, int $hidden, int $archive_time, int $is_archive)
  {
    $stmt = $this->conn_rw->prepare("INSERT INTO `boards` "
        . "(`shortname`, `longname`, `worksafe`, `pages`, `perpage`, `privilege`, `swf_board`,"
        . " `is_archive`, `first_crawl`, `last_crawl`, `group`, `hidden`,`archive_time`) VALUES "
        . "(:short, :long, :ws, :pages, :per, :priv, :swf, :is_archive, UNIX_TIMESTAMP(), 0, :group, :hidden,:archivetime)");
    $stmt->execute([':short' => $shortname, ':long' => $longname, ':ws' => $worksafe,
        ':pages' => $pages, ':per' => $per_page, ':priv' => $privilege, ':swf' => $swf_board,
        ':group' => $group, ':hidden' => $hidden, ':archivetime' => $archive_time, ':is_archive' => $is_archive]);
    $sn = alphanum($shortname);

    // Set up board tables
    $this->conn_rw->exec(str_replace(['%BOARD%'], [$sn], file_get_contents(Site::getPath()."/sql/newboard.sql")));
  }

  /**
   * Tries to load the given board shortname, else throws a NotFoundException.
   *
   * @param string $shortname the shortname of the board to look up
   * @return Board the board
   * @throws NotFoundException if the board can't be found.
   */
  function getBoard(string $shortname): Board
  {
    $stmt = $this->conn_ro
        ->prepare("SELECT * FROM `boards` WHERE `shortname`= :name");
    $stmt->execute([':name' => $shortname]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($b === FALSE) {
      throw new NotFoundException("The given board was not found.");
    }
    return new ImageBoard\Board($b);
  }

  /**
   * Gets the number of threads in a board.
   * @param Board $shortname
   * @return int number of threads
   */
  function getNumberOfThreads(Board $b): int
  {
    $board = alphanum($b->getName());
    return $this->conn_ro
        ->query("SELECT COUNT(threadid) as count FROM `{$board}_thread`")
        ->fetch(PDO::FETCH_ASSOC)['count'];
  }

  /**
   * Gets the number of posts in a board.
   * @param Board $shortname
   * @return int number of posts.
   */
  function getNumberOfPosts(Board $b): int
  {
    $board = alphanum($b->getName());
    return $this->conn_ro
        ->query("SELECT COUNT(`no`) as count FROM `{$board}_post`")
        ->fetch(PDO::FETCH_ASSOC)['count'];
  }

  // Thread functions

  /**
   * Get a thread from the database. This does <em>not</em> load posts into the thread.
   * @param Board $board the board to which the thread belongs.
   * @param int $id the ID of the thread OP
   * @return Thread the thread
   */
  function getThread(Board $b, int $id): Thread
  {
    $board = alphanum($b->getName());
    $stmt = $this->conn_ro
        ->prepare("SELECT * FROM `{$board}_thread` WHERE `threadid`=:id");
    if ($stmt->execute([":id" => $id]) && $stmt->rowCount() === 1) {
      return Thread::fromArray($b, $stmt->fetch(PDO::FETCH_ASSOC));
    }
    throw new NotFoundException("Thread $id not found on board $board");
  }

  /**
   * Gets a page worth of threads for the given board.
   * @param Board $board
   * @param int $pageNo
   * @param bool $onlyActive only show active threads
   * @return Thread[] array of threads.
   */
  function getPageOfThreads(Board $board, int $pageNo, bool $onlyActive = false): array
  {
    if ($pageNo < 1) {
      throw new InvalidArgumentException("Invalid page number given");
    }
    $pageNo--;
    $prefix = alphanum($board->getName()) . "_";
    $perpage = (int)$board->getThreadsPerPage();
    $tTable = $prefix . "thread";
    $number = $pageNo * $perpage;
    $pageQuery = "SELECT $tTable.*  FROM $tTable "
        . ($onlyActive ? "WHERE $tTable.active = 1 " : "")
        . "ORDER BY $tTable.active DESC, $tTable.sticky DESC, $tTable.lastreply DESC "
        . "LIMIT $number,$perpage";
    $q = $this->conn_ro->query($pageQuery);
    return array_map(function ($row) use ($board) {
      return Thread::fromArray($board, $row);
    }, $q->fetchAll(PDO::FETCH_ASSOC));
  }

  /**
   * Gets a catalog's worth of threads for the given board.
   * @param Board $board
   * @return Thread[] array of threads
   */
  function getCatalog(Board $board): array
  {

  }

  /**
   * @param Board $board
   * @param int $resto
   * @param $name
   * @param $trip
   * @param $email
   * @param $sub
   * @param $com
   * @return Post
   * @throws NotFoundException
   */
  function addPost(Board $board, int $resto, $name, $trip, $email, $sub, $com): Post {
    if($board->isArchive()) {
      return null;
    }

    $time = time();
    $stmt = $this->conn_rw->prepare("INSERT INTO `{$board->getName()}_post` "
        . "(no, resto, time, name, trip, email, sub, com) VALUES (0, :resto, :time, :name, :trip, :email, :sub, :com)");
    $stmt->execute([
      ':resto'=>$resto, ':time'=>$time, ':name'=>$name, ':trip'=>$trip,
      ':email'=>$email, ':sub'=>$sub, ':com'=>$com
    ]);
    $postId = $this->conn_rw->lastInsertId();
    $clause = $resto == 0 ? ", `resto`=`doc_id`" : "";
    $this->conn_rw->exec("UPDATE `{$board->getName()}_post` SET `no`=`doc_id` $clause WHERE `doc_id`=$postId");

    if($resto == 0) {
      $this->conn_rw->exec("INSERT INTO `{$board->getName()}_thread` (threadid, active, sticky, closed, archived, custom_spoiler, replies, images, last_crawl, lastreply) "
      ." VALUES ($postId, 1, 0, 0, 0, 0, 0, 0, $time, $time)");
    }
    else {
      $this->conn_rw->exec("UPDATE `{$board->getName()}_thread` SET `replies`=`replies`+1, `last_crawl`='$time', `lastreply`='$time' WHERE `threadid`='$resto'");
    }
    $this->conn_rw->exec("UPDATE `boards` SET `last_crawl`='$time' WHERE `boards`.`shortname` = '{$board->getName()}'");
    return $this->getPost($board, $postId);
  }

  // Post functions

  /**
   * Gets a post.
   * @param Board $board
   * @param int $id
   * @return Post the post
   */
  function getPost(Board $board, int $id): Post
  {
    $postTbl = alphanum($board->getName()) . "_post";
    $stmt = $this->conn_ro->prepare("SELECT * FROM `$postTbl` WHERE `no`=:no");
    if ($stmt->execute([':no' => $id]) == false || $stmt->rowCount() === 0) {
      throw new NotFoundException("No such post $id exists on board {$board->getName()} in this archive");
    }
    return new Post($stmt->fetch(PDO::FETCH_ASSOC), $board);
  }

  /**
   * Gets all posts in a thread.
   * @param Thread $t
   * @return Post[] the posts in order by ID.
   */
  function getAllPosts(Thread $t): array
  {
    $dbl = $this->conn_ro;
    $board = alphanum($t->getBoard()->getName());
    $board = $board . "_";
    $threadid = $t->getThreadId();
    $stmt = $dbl->prepare("SELECT * FROM `{$board}post` WHERE `resto`=:thread ORDER BY `no` ASC");

    if (!$stmt->execute([':thread' => $t->getThreadId()]) || $stmt->rowCount() === 0) {
      throw new NotFoundException("Thread #$threadid exists, but contains no posts.");
    }
    return array_map(function ($row) use ($t) {
      return new Post($row, $t->getBoard());
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  /**
   * Get the last N replies
   * @param Thread $t
   * @param int $n
   * @return Post[]
   * @throws Exception
   */
  function getLastNReplies(Thread $t, int $n)
  {
    $board = $t->getBoard();
    $threadId = $t->getThreadId();
    $pTable = $board->getName() . "_post";
    $query = "SELECT * FROM $pTable WHERE resto='$threadId' AND `resto` <> `no` ORDER BY `no` DESC LIMIT 0,$n";
    $result = $this->conn_ro->query($query);
    if ($result->rowCount() > 0) {
      return array_reverse(array_map(function ($el) use ($board) {
        return new Post($el, $board);
      }, $result->fetchAll(PDO::FETCH_ASSOC)));
    }
    throw new Exception("Thread $threadId contains no replies!");
  }

  /**
   * Search functions
   */

  function getPostsByMD5(ImageBoard\Board $b, string $md5_hex, int $num = 500, int $offset = 0):\Model\PostSearchResult
  {
    $pTable = $b->getName() . '_post';
    $md5 = alphanum($md5_hex);
    $count = $this->conn_ro->query("SELECT COUNT(*) FROM `$pTable` WHERE `md5`=UNHEX('$md5')")->fetchColumn();
    $q = $this->conn_ro->prepare("SELECT * FROM `$pTable` WHERE `md5`=UNHEX(?) LIMIT $offset, $num");
    $posts = [];
    if ($q->execute([$md5_hex]) !== FALSE) {
      $posts = array_map(function ($row) use ($b) {
        return new Post($row, $b);
      }, $q->fetchAll(PDO::FETCH_ASSOC));
    }
    return new PostSearchResult($count, $posts);
  }

  function getPostsByID(ImageBoard\Board $b, string $id, int $num = 500, int $offset = 0):\Model\PostSearchResult
  {
    $pTable = $b->getName() . '_post';
    $idQ = $this->conn_ro->quote($id);
    $count = $this->conn_ro->query("SELECT COUNT(*) FROM `$pTable` WHERE `id`=$idQ")->fetchColumn();
    $q = $this->conn_ro->prepare("SELECT * FROM `$pTable` WHERE `id`=? LIMIT $offset, $num");
    $posts = [];
    if ($q->execute([$id]) !== FALSE) {
      $posts = array_map(function ($row) use ($b) {
        return new Post($row, $b);
      }, $q->fetchAll(PDO::FETCH_ASSOC));
    }
    return new PostSearchResult($count, $posts);
  }

  function getPostsByTrip(ImageBoard\Board $b, string $trip, int $num = 500, int $offset = 0):\Model\PostSearchResult
  {
    $pTable = $b->getName() . '_post';
    $tripQ = $this->conn_ro->quote($trip);
    $count = $this->conn_ro->query("SELECT COUNT(*) FROM `$pTable` WHERE `trip`=$tripQ")->fetchColumn();
    $q = $this->conn_ro->prepare("SELECT * FROM `$pTable` WHERE `trip`=? LIMIT $offset, $num");
    $posts = [];
    if ($q->execute([$trip]) !== FALSE) {
      $posts = array_map(function ($row) use ($b) {
        return new Post($row, $b);
      }, $q->fetchAll(PDO::FETCH_ASSOC));
    }
    return new PostSearchResult($count, $posts);
  }

  /**
   * Gets a user given a username and password.
   *
   * @param string $username
   * @param string $password
   * @throws NotFoundException if the user cannot be found.
   */
  function getUser(string $username, string $password):User
  {
    $hashedPass = md5($password);
    /** @var PDOStatement $stmt */
    $stmt = $this->conn_ro
        ->prepare("SELECT * FROM `users` WHERE `username`=:name AND `password_hash`=UNHEX('$hashedPass')");
    if ($stmt->execute([':name' => $username]) && $stmt->rowCount() == 1) {
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return new User($result['uid'], $result['username'], $result['privilege'], $result['theme']);
    } else {
      throw new Exception("Username/password pair not found.");
    }
  }

  /**
   * Get all requests from the db. By default only shows outstanding requests.
   *
   * @param bool $acceptedOnly Set to false to include accepted requests.
   * @return array Request info
   */
  function getRequests(bool $acceptedOnly = true):array
  {
    $query = "SELECT * FROM `request`" . ($acceptedOnly ? " WHERE `accepted`=0" : "");
    $q = $this->conn_ro->query($query);
    if ($q !== false) {
      return array_map(function ($key) {
        $key['password'] = bin2hex($key['password']);
        return $key;
      }, $q->fetchAll(PDO::FETCH_ASSOC));
    } else {
      return ["Error"];
    }
  }


  /**
   * Gets a list of active media.
   *
   * @param Board $board
   * @return Board[]
   */
  function getActiveMedia(ImageBoard\Board $board): array
  {
    $pt = "`" . alphanum($board->getName()) . "_post`";
    $tt = "`" . alphanum($board->getName()) . "_thread`";
    $threads = implode(',', $this->conn_ro
        ->query("SELECT `threadid` FROM $tt WHERE `active`='1'")
        ->fetchAll(PDO::FETCH_COLUMN, 0));
    if ($threads === "") {
      return [];
    }
    $q = "SELECT "
        . "$pt.`md5`,$pt.`fsize`,$pt.`w`,$pt.`h`,$pt.`ext`,$pt.`tim`,$pt.`filename`, IF($pt.`no`=$pt.`resto`,1,0) as op "
        . "FROM $pt WHERE $pt.`resto` IN ($threads) AND "
        . "$pt.`md5` != '' AND $pt.`deleted` = '0'";
    $query = $this->conn_ro->query($q);
    $pics = [];
    while ($reply = $query->fetch(PDO::FETCH_ASSOC)) {
      $pics[] = ["md5" => base64_encode($reply['md5']),
          "tim" => (int)$reply['tim'],
          "filename" => $reply['filename'],
          "ext" => $reply['ext'],
          "fsize" => (int)$reply['fsize'],
          "w" => (int)$reply['w'],
          "h" => (int)$reply['h'],
          "op" => (int)$reply['op']];
    }
    return $pics;
  }

  private $banned_hashes = null;

  public function getBannedHashes()
  {
    if ($this->banned_hashes == null) {
      $q = $this->conn_ro->query("SELECT `hash` FROM `banned_hashes`");

      $ret = array_map(function ($val) {
        return bin2hex($val);
      }, $q->fetchAll(PDO::FETCH_COLUMN, 0));

      $this->banned_hashes = $ret;
    }
    return $this->banned_hashes;
  }

  public function getReports()
  {
    $query = $this->conn_ro->query("SELECT *, COUNT(*) AS count FROM `reports` GROUP BY `no` ORDER BY count DESC, time ASC");
    $ret = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
      $md5 = $this->conn_ro
          ->query("SELECT md5 FROM {$row['board']}_post WHERE `no`={$row['no']}")
          ->fetchColumn();
      $row['md5'] = $md5;
      $ret[] = $row;
    }
    return $ret;
  }

  public function addReport(ImageBoard\Board $board, int $post, int $thread)
  {
    $time = time();
    $uid = Site::getUser()->getUID();
    $ip = $_SERVER['REMOTE_ADDR'];
    $this->conn_rw
        ->query("INSERT INTO `reports` (`uid`,`board`,`time`,`ip`,`no`,`threadid`) "
            . "VALUES ('$uid','{$board->getName()}',$time,'$ip',$post,$thread)");
  }


  public function getNumberOfReports()
  {
    $data = $this->conn_ro->query("SELECT COUNT(*) AS count FROM `reports`");
    if ($data !== FALSE) {
      return $data->fetchColumn();
    }
    return 0;
  }

  public function isBanned(string $ip)
  {
    try {
      $dbl = Config::getPDOConnection();
      $ip = $dbl->quote($ip);
      return $dbl->query("SELECT `ip` FROM `bans` WHERE `ip`='$ip'")->rowCount() > 0;
    } catch (Exception $ex) {
      return false;
    }
  }

  public function getUsers():array
  {
    $query = Config::getPDOConnection()->query("SELECT * FROM `users`");
    if ($query === FALSE) {
      return [];
    }
    return $query->fetchAll(PDO::FETCH_ASSOC);
  }

  public function changePassword(int $uid, string $old, string $new):bool
  {
    $dbl = Config::getMysqliConnectionRW();
    $user = $dbl->query("SELECT * FROM `users` WHERE `uid`=$uid")->fetch_assoc();
    if ($user['password_hash'] == md5($old, true)) {
      $new = md5($new);
      $dbl->query("UPDATE `users` SET `password_hash`=UNHEX('$new') WHERE `uid`=$uid");
      if (!$dbl->errno) {
        return true;
      }
    }
    return false;
  }

  public function getAllNewsArticles()
  {
    $query = "SELECT `users`.`username`,`users`.`uid`,`news`.`article_id`,`news`.`title`,`news`.`content`,`news`.`time`,`news`.`update` FROM `news` JOIN `users` ON `news`.`author_id`=`users`.`uid` WHERE `news`.`time` < UNIX_TIMESTAMP() ORDER BY `news`.`article_id` DESC";
    $q = $this->conn_ro->query($query);
    return $q->fetchAll(\PDO::FETCH_ASSOC);
  }

}
