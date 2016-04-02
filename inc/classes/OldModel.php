<?php
/**
 * Class for getting, changing, and removing data. Expect big additions to this
 * as I update the site in order to make maintenance easier.
 * 
 */
class OldModel {
    /**
     * Fetches a complete thread.
     * 
     * @param string $board The board shortname.
     * @param int $no The post id#.
     * @param boolean $deleted If true, only return [Deleted] posts.
     * @return array Mysqli result sets for thread, then post.
     */
    static function getThread($board,$no,$deleted=false){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $board = $board."_";
        $no = $dbl->real_escape_string($no);
        $threadQ = $dbl->query("SELECT * FROM `{$board}thread` WHERE `threadid`='$no'");
        
        $postQ = $dbl->query("SELECT * FROM `{$board}post` WHERE `resto`='$no' ".($deleted?"AND (`deleted`='1' OR `no`=`resto`)":"")."ORDER BY `no` ASC");
        if(!($threadQ->num_rows > 0))
            throw new NotFoundException ("Thread does not exist in the archive.");
        return array($threadQ,$postQ);
    }
    /**
     * Fetches a single post query object.
     * 
     * @param string $board The board shortname.
     * @param int $no The post id#.
     * @return mysqli_result Mysqli result set.
     */
    static function getPostQuery($board,$no){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $board = $board."_";
        $no = $dbl->real_escape_string($no);
        $postQ = $dbl->query("SELECT * FROM `{$board}post` WHERE `no`='$no'");
        if($postQ == false || $postQ->num_rows === 0){
            throw new NotFoundException("No such post $no exists in this archive");
        }
        return $postQ;
    }
    
    /**
     * Gets a query for the board catalog.
     * @param Board $board
     * @param boolean $active
     * @return mysqli::query
     */
    static function getCatalog($board,$active = true){
        $dbl = Config::getMysqliConnection();
        $board_shortname = $dbl->real_escape_string($board->getName());
        $prefix = $board_shortname."_";
        $pTable = $prefix."post";
        $tTable = $prefix."thread";
        $pageQuery = "SELECT {$tTable}.*, {$pTable}.*  FROM {$tTable} LEFT JOIN {$pTable} ON {$pTable}.no = {$tTable}.threadid ";
        if($active) $pageQuery .= "WHERE {$tTable}.active = 1 ";
        $pageQuery .= "ORDER BY {$tTable}.lastreply DESC LIMIT 0,{$board->getMaxActiveThreads()}";
        $q = $dbl->query($pageQuery);
        return $q;
    }
    
    static function getPostsWithLinkToPost($board,$post){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $post = (int)$post;
        $tPost = $board."_post";
        $tCom = $board."_comment";
        $query = "SELECT `$tPost`.`no` FROM `$tCom` ".
                 "JOIN `$tPost` ON `$tPost`.`no`=`$tCom`.`no` ".
                 "WHERE MATCH(`$tCom`.`comment_plaintext`) AGAINST('$post') ".
                 "AND `$tPost`.`comment` LIKE '%;$post%' ".
                 "ORDER BY `$tPost`.`no` ASC";
        $ret = array();
        $result = $dbl->query($query);
        while($row = $result->fetch_array()){
            $ret[] = (int)$row[0];
        }
        return $ret;
    }
    
    static function getDeletedPosts($board){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $prefix = $board."_";
        $pTable = $prefix."post";
        $query = "SELECT * FROM $pTable WHERE deleted=1";
        return $dbl->query($query);
    }
    
    static function getFileDeletedPosts($board){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $prefix = $board."_";
        $pTable = $prefix."post";
        $query = "SELECT * FROM $pTable WHERE file_deleted=1";
        return $dbl->query($query);
    }
    
    /**
     * Gets an array of the last <i>n</i> posts in the thread.
     * 
     * @param string $board
     * @param int $thread
     * @param int $n number of posts to get
     * @return array posts
     */
    static function getLastNPosts($board,$thread, int $n){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $threadId = $dbl->real_escape_string($thread);
        $prefix = $board."_";
        $pTable = $prefix."post";
        $query = "SELECT * FROM $pTable WHERE resto='$threadId' AND `resto` <> `no` ORDER BY `no` DESC LIMIT 0,$n";
        $result = $dbl->query($query);
        $postArr = array();
        if($result->num_rows > 0){
          while($row = $result->fetch_assoc())
              $postArr[] = $row;
        }
        else
        {
          throw new Exception("Thread $thread contains no posts!");
        }
        return array_reverse($postArr);
    }
    
    /**
     * Get the last few deleted posts in case you mistakenly delete one from the archive.
     * 
     * @param Board|string $board
     */
    static function getLastNDeletedPosts($board,$n){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $n = abs((int)$n);
        $prefix = $board."_";
        $query = "SELECT * FROM `{$prefix}deleted` ORDER BY `no` DESC LIMIT 0,$n";
        $result = $dbl->query($query);
        $postArr = array();
        while($row = $result->fetch_assoc())
            $postArr[] = $row;
        return $postArr;
    }
    
    /**
     * 
     * @param int|string $tim
     * @param string $board
     * @return array `md5`,`ext`
     */
    static function getMD5FromTim($tim,$board){
        $dbl = Config::getMysqliConnection();
        $board = $dbl->real_escape_string($board);
        $tim = $dbl->real_escape_string($tim);
        $q = $dbl->query("SELECT `md5`,`ext` FROM `{$board}_post` WHERE `tim`='$tim'");
        if(!$dbl->errno)
            return $q->fetch_assoc();
        else
            return false;
    }
    
    public static function getReports(){
        $dbl = Config::getMysqliConnection();
        $query = $dbl->query("SELECT *, COUNT(*) as count FROM `reports` GROUP BY `no` ORDER BY count DESC, time ASC");
        
        $ret = [];
        while($row = $query->fetch_assoc()){
            $md5 = $dbl->query("SELECT md5 FROM {$row['board']}_post WHERE `no`={$row['no']}")->fetch_array()[0];
            $row['md5'] = $md5;
            $ret[] = $row;
        }
        return $ret;
    }
    public static function getNumberOfReports(){
        $dbl = Config::getMysqliConnection();
        $data = $dbl->query("SELECT COUNT(*) as count FROM `reports`")->fetch_assoc();
        $ret = $data['count'];
        return $ret;
    }
    public static function ban($ip,$reason,$expires = 0){
        $dbl = Config::getMysqliConnectionRW();
        $ip = $dbl->real_escape_string($ip);
        $reason = $dbl->real_escape_string($reason);
        $expires = $dbl->real_escape_string($expires);
        $dbl->query("INSERT INTO `bans` (`ip`,`reason`,`expires`) VALUES ('$ip','$reason','$expires')");
    }
    public static function banned($ip){
      try{
        $dbl = Config::getMysqliConnection();
        $ip = $dbl->real_escape_string($ip);
        return $dbl->query("SELECT `ip` FROM `bans` WHERE `ip`='$ip'")->num_rows > 0;
      } catch(Exception $ex) {
        return false;
      }
    }
    public static function getBanInfo($ip){
        $dbl = Config::getMysqliConnection();
        $ip = $dbl->real_escape_string($ip);
        return $dbl->query("SELECT * FROM `bans` WHERE `ip`='$ip'")->fetch_assoc();
    }
    public static function banHash($hash){
        $dbl = Config::getMysqliConnectionRW();
        $hashcln = $dbl->real_escape_string($hash);
        if(bin2hex(hex2bin($hashcln)) === $hash){
            $dbl->query("INSERT IGNORE INTO `banned_hashes` (`hash`) VALUES (UNHEX('$hashcln'))");
            if($dbl->errno){
                throw new Exception($dbl->error);
            }
        }
        else{
            throw new Exception("Invalid hash: $hash");
        }
    }
    static $banned_hashes = null;
    public static function getBannedHashes(){
      if(self::$banned_hashes != null){
        return self::$banned_hashes;
      }
      $dbl = Config::getMysqliConnection();
      $q = $dbl->query("SELECT `hash` FROM `banned_hashes`");
      while($row = $q->fetch_assoc()){
        $ret[] = bin2hex($row['hash']);
      }
      self::$banned_hashes = $ret;
      return $ret;
    }
    public static function deletePost($no,$board){
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("INSERT INTO `{$board}_deleted` (SELECT * FROM `{$board}_post` WHERE `{$board}_post`.`no`=$no)");
        if(!$dbl->errno){
            $dbl->query("DELETE FROM `{$board}_post` WHERE `no`=$no");
            if(!$dbl->errno)
                self::deleteReport($no,$board);
            else
                throw new Exception("Query failed: ".$dbl->error);
        }
        else
            throw new Exception("Query failed: ".$dbl->error);
    }
    public static function deleteReport($no,$board){
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("DELETE FROM `reports` WHERE `no`=$no AND `board`='$board'");
        if($dbl->errno){
            throw new Exception("Query failed.");
        }
    }
    public static function banReporter($no,$board){
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $query = $dbl->query("SELECT `ip` FROM `reports` WHERE `no`=$no AND `board`='$board'");
        if(!$dbl->errno){
            while($row = $query->fetch_assoc()){
                self::ban($row['ip'],"Frivolous reporting");
            }
            self::deleteReport($no, $board);
        }
        else
            throw new Exception("Query failed.");
    }
    public static function restorePost($no,$board){
        $dbl = Config::getMysqliConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("INSERT INTO `{$board}_post` (SELECT * FROM `{$board}_deleted` WHERE `{$board}_deleted`.`no`=$no)");
        if(!$dbl->errno){
            $dbl->query("DELETE FROM `{$board}_deleted` WHERE `no`=$no");
            if($dbl->errno)
                throw new Exception("Delete query failed: ".$dbl->error);
        }
        else
            throw new Exception("Restore query failed: ".$dbl->error);
    }
    
    public static function changePassword($uid,$old,$new){
        $dbl = Config::getMysqliConnectionRW();
        $uid = (int)$uid;
        $user = $dbl->query("SELECT * FROM `users` WHERE `uid`=$uid")->fetch_assoc();
        if($user['password_hash']==md5($old,true)){
            $new = md5($new);
            $dbl->query("UPDATE `users` SET `password_hash`=UNHEX('$new') WHERE `uid`=$uid");
            if(!$dbl->errno)
                return true;
        }
        
        return false;
    }
    
    public static function getUsers(){
        $query = Config::getMysqliConnection()->query("SELECT * FROM `users`");
        $ret = [];
        while($user = $query->fetch_assoc()){
            $ret[] = $user;
        }
        return $ret;
    }
    
    public static function addUser($username,$password,$privilege,$theme){
        $db = Config::getMysqliConnectionRW();
        $themes = array("yotsuba"=>"yotsuba","tomorrow"=>"tomorrow");
        $theme = $themes[$theme];
        $username = $db->real_escape_string($username);
        $password = md5($password);
        $privilege = (int)$privilege;
        $db->query("INSERT INTO `users` (`username`,`password_hash`,`privilege`,`theme`) VALUES ('$username',UNHEX('$password'),'$privilege','$theme')");
        if(!$db->errno){
            return true;
        }
        throw new Exception($db->error);
    }
    
    public static function getAllNewsArticles(){
        $db = Config::getMysqliConnection();
        $query = "SELECT `users`.`username`,`users`.`uid`,`news`.`article_id`,`news`.`title`,`news`.`content`,`news`.`time`,`news`.`update` FROM `news` JOIN `users` ON `news`.`author_id`=`users`.`uid` WHERE `news`.`time` < UNIX_TIMESTAMP() ORDER BY `news`.`article_id` DESC";
        $q = $db->query($query);
        return $q->fetch_all(MYSQLI_ASSOC);
    }
    public static function updateUserTheme($uid, $theme) {
      try {
        $db = Config::getMysqliConnectionRW();
        $q = $db->prepare("UPDATE `users` SET `theme`=? WHERE `uid`=?");
        $q->bind_param("si", $theme, $uid);
        $q->execute();
      } catch (Exception $ex) {

      }
    }
    public static function setUpTables(){
        $db = Config::getMysqliConnectionRW();
        $err = false;
        /*
         * Table: Users
         */
        echo "Creating table `users`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `users` (
          `uid` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `password_hash` binary(16) NOT NULL,
          `privilege` int(11) NOT NULL,
          `theme` varchar(24) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yotsuba',
          `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`uid`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }

        /*
         * Table: Searches
         */
        echo "Creating table `search`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `search` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `timestamp` int(11) NOT NULL,
          `query` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
          `results` int(11) NOT NULL,
          `exec_time` double NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }

        /*
         * Table: News
         */
        echo "Creating table `news`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `news` (
          `article_id` int(11) NOT NULL AUTO_INCREMENT,
          `author_id` int(11) NOT NULL,
          `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `time` int(11) NOT NULL,
          `content` text COLLATE utf8_unicode_ci NOT NULL,
          `update` int(11) NOT NULL,
          PRIMARY KEY (`article_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;");
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }

        /*
         * Table: Bans
         */
        echo "Creating table `bans`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `bans` (
          `ip` varchar(44) COLLATE utf8_unicode_ci NOT NULL,
          `reason` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`ip`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }

        /*
         * Table: Reports
         */
        echo "Creating table `reports`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `reports` (
          `no` int(11) NOT NULL,
          `uid` int(11) NOT NULL,
          `threadid` int(11) NOT NULL,
          `board` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
          `time` int(11) NOT NULL,
          UNIQUE KEY `no` (`no`,`uid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;");
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }
        
        /*
         * Table: Boards
         */
        echo "Creating table `boards`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `boards` (
          `id` int(11) NOT NULL,
          `shortname` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
          `longname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `worksafe` tinyint(4) NOT NULL,
          `pages` int(11) NOT NULL,
          `perpage` int(11) NOT NULL,
          `privilege` int(11) NOT NULL DEFAULT '0',
          `swf_board` tinyint(4) NOT NULL DEFAULT '0',
          `is_archive` tinyint(1) NOT NULL DEFAULT '1',
          `first_crawl` int(11) NOT NULL,
          `last_crawl` int(11) NOT NULL DEFAULT '0',
          `group` int(11) NOT NULL
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");
        if($db->error == ""){
          $db->query("ALTER TABLE `boards`
            ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `shortname` (`shortname`);");
        }
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }
        /*
         * Table: Requests
         */
        echo "Creating table `requests`...".PHP_EOL;
        $db->query("CREATE TABLE IF NOT EXISTS `request` (
          `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
          `reason` text COLLATE utf8_unicode_ci NOT NULL,
          `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `password` binary(16) NOT NULL,
          `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `time` int(11) NOT NULL,
          `accepted` tinyint(1) NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        if($db->error == ""){
          $db->query("ALTER TABLE `request` ADD PRIMARY KEY (`ip`);");
        }
        if ($db->error == "") {
            echo "Success!" . PHP_EOL;
        } else {
            $err = true;
            echo "Error! : " . $db->error;
        }
        return !$err;
    }
}