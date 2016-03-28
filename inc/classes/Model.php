<?php

class Model implements \IModel {

  /** @var \IModel instance  */
  private static $instance;

  public static function get(): \Model {
    if (isset(self::$instance))
      return self::$instance;
    self::$instance = new Model();
    return self::$instance;
  }

  /** @var PDO */
  private $conn_rw;

  /** @var PDO */
  private $conn_ro;

  private function __construct() {
    $this->conn_ro = Config::getPDOConnection();
    $this->conn_rw = Config::getPDOConnectionRW();
  }

  // Board functions

  /**
   * Get all the boards in this DB.
   * @param bool $hidden Show hidden boards?
   * @return Board[] array of all boards in this database.
   */
  function getBoards(bool $hidden = false): array {
    $clause = $hidden ? "": "WHERE `hidden`=0";
    $b = $this->conn_ro
            ->query("SELECT * FROM `boards` $clause ORDER BY `group` ASC, `shortname` ASC")
            ->fetchAll();
    $ret = [];
    foreach ($b as $boardinfo) {
      $ret[$boardinfo['shortname']] = new \Board($boardinfo);
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
  function addBoard(string $shortname, string $longname, bool $worksafe,
          int $pages, int $per_page, int $privilege, bool $swf_board,
          int $group, bool $hidden) {
    $stmt = $this->conn_rw->prepare("INSERT INTO `boards` "
            . "(`shortname`, `longname`, `worksafe`, `pages`, `perpage`, `privilege`, `swf_board`,"
            . " `is_archive`, `first_crawl`, `last_crawl`, `group`, `hidden`) VALUES "
            . "(:short, :long, :ws, :pages, :per, :priv, :swf, 1, UNIX_TIMESTAMP(), 0, :group, :hidden)");
    $stmt->execute([':short'=>$shortname, ':long'=>$longname, ':ws'=>$worksafe, 
      ':pages'=>$pages, ':per'=>$per_page, ':priv'=>$privilege, ':swf'=>$swf_board,
      ':group'=>$group, ':hidden'=>$hidden]);
  }
  
  /**
   * Tries to load the given board shortname, else throws a NotFoundException.
   * 
   * @param string $shortname the shortname of the board to look up
   * @return Board the board
   * @throws NotFoundException if the board can't be found.
   */
  function getBoard(string $shortname): Board {
    $stmt = $this->conn_ro
            ->prepare("SELECT * FROM `boards` WHERE `shortname`= :name");
    $stmt->execute([':name' => $shortname]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($b === FALSE) {
      throw new NotFoundException("The given board was not found.");
    }
    return new \Board($b);
  }

  /**
   * Gets the number of threads in a board.
   * @param Board $shortname
   * @return int number of threads
   */
  function getNumberOfThreads(Board $b): int {
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
  function getNumberOfPosts(Board $b): int {
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
  function getThread(Board $b, int $id): Thread {
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
   * @return Thread[] array of threads.
   */
  function getPageOfThreads(Board $board, int $pageNo): array {
    if ($pageNo < 1) {
      throw new InvalidArgumentException("Invalid page number given");
    }
    $pageNo--;
    $prefix = alphanum($board->getName()) . "_";
    $perpage = (int) $board->getThreadsPerPage();
    $tTable = $prefix . "thread";
    $number = $pageNo * $perpage;
    $pageQuery = "SELECT $tTable.*  FROM $tTable "
            . "WHERE $tTable.active = 1 "
            . "ORDER BY ($tTable.sticky * $tTable.active) DESC, $tTable.lastreply DESC "
            . "LIMIT $number,$perpage";
    $q = $this->conn_ro->query($pageQuery);
    return array_map(function($row) use($board) {
      return Thread::fromArray($board, $row);
    }, $q->fetchAll(PDO::FETCH_ASSOC));
  }

  /**
   * Gets a catalog's worth of threads for the given board.
   * @param Board $board
   * @return Thread[] array of threads
   */
  function getCatalog(Board $board): array {
    
  }

  // Post functions

  /**
   * Gets a post.
   * @param Board $board
   * @param int $id
   * @return Post the post
   */
  function getPost(Board $board, int $id): Post {
    $postTbl = alphanum($board->getName()) . "_post";
    $stmt = $this->conn_ro->prepare("SELECT * FROM `$postTbl` WHERE `no`=:no");
    if ($stmt->execute([':no' => $id]) == false || $stmt->rowCount() === 0) {
      throw new NotFoundException("No such post $id exists on board {$board->getName()} in this archive");
    }
    return new Post($stmt->fetch(PDO::FETCH_ASSOC));
  }

  /**
   * Gets all posts in a thread.
   * @param Thread $t
   * @return Post[] the posts in order by ID.
   */
  function getAllPosts(Thread $t): array {
    $dbl = $this->conn_ro;
    $board = alphanum($t->getBoard()->getName());
    $board = $board . "_";
    $threadid = $t->getThreadId();
    $stmt = $dbl->prepare("SELECT * FROM `{$board}post` WHERE `resto`=:thread ORDER BY `no` ASC");
    
    if (!$stmt->execute([':thread'=>$t->getThreadId()]) || $stmt->rowCount() === 0) {
      throw new NotFoundException("Thread #$threadid exists, but contains no posts.");
    }
    return array_map(function($row) use($t) {
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
  function getLastNReplies(Thread $t, int $n) {
    $board = $t->getBoard();
    $threadId = $t->getThreadId();
    $pTable = $board->getName()."_post";
    $query = "SELECT * FROM $pTable WHERE resto='$threadId' AND `resto` <> `no` ORDER BY `no` DESC LIMIT 0,$n";
    $result = $this->conn_ro->query($query);
    if ($result->rowCount() > 0) {
      return array_reverse(array_map(function($el){ return new Post($el); }, $result->fetchAll(PDO::FETCH_ASSOC)));
    }
    throw new Exception("Thread $threadId contains no replies!");
  }

  /**
   * Gets a user given their ID.
   * 
   * @param int $id
   * @throws NotFoundException if the user cannot be found.
   */
  function getUserById(int $id): User {
    
  }

  /**
   * Gets a user given a username and password.
   * 
   * @param string $username
   * @param string $password
   * @throws NotFoundException if the user cannot be found.
   */
  function getUser(string $username, string $password):User {
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
   * Gets a list of active media.
   * 
   * @param Board $board
   * @return Board[]
   */
  function getActiveMedia(\Board $board): array {
    $pt = "`".alphanum($board->getName()) . "_post`";
    $tt = "`".alphanum($board->getName()) . "_thread`";
    $threads = implode(',', $this->conn_ro
                    ->query("SELECT `threadid` FROM $tt WHERE `active`='1'")
                    ->fetchAll(PDO::FETCH_COLUMN, 0));
    if ($threads === "") {
      return [];
    }
    $q = "SELECT "
            . "$pt.`md5`,$pt.`fsize`,$pt.`w`,$pt.`h`,$pt.`ext`,$pt.`tim`,$pt.`filename` "
            . "FROM $pt WHERE $pt.`resto` IN ($threads) AND "
            . "$pt.`md5` != '' AND $pt.`deleted` = '0'";
    $query = $this->conn_ro->query($q);
    $pics = [];
    while ($reply = $query->fetch(PDO::FETCH_ASSOC)) {
      $pics[] = [ "md5" => base64_encode($reply['md5']),
        "tim" => (int) $reply['tim'],
        "filename" => $reply['filename'],
        "ext" => $reply['ext'],
        "fsize" => (int) $reply['fsize'],
        "w" => (int) $reply['w'],
        "h" => (int) $reply['h']];
    }
    return $pics;
  }

  private $banned_hashes = null;

  public function getBannedHashes() {
    if ($this->banned_hashes == null) {
      $q = $this->conn_ro->query("SELECT `hash` FROM `banned_hashes`");

      $ret = array_map(function($val) {
        return bin2hex($val);
      }, $q->fetchAll(PDO::FETCH_COLUMN, 0));

      $this->banned_hashes = $ret;
    }
    return $this->banned_hashes;
  }

  public function getReports() {
    $query = $this->conn_ro->query("SELECT *, COUNT(*) as count FROM `reports` GROUP BY `no` ORDER BY count DESC, time ASC");
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

  public function addReport(\Board $board, int $post, int $thread) {
    $time = time();
    $uid = Site::getUser()->getUID();
    $ip = $_SERVER['REMOTE_ADDR'];
    $this->conn_rw
            ->query("INSERT INTO `reports` (`uid`,`board`,`time`,`ip`,`no`,`threadid`) "
                    . "VALUES ('$uid','{$board->getName()}',$time,'$ip',$post,$thread)");
  }

}
