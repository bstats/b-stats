<?php
/**
 * Class for getting, changing, and removing data. Expect big additions to this
 * as I update the site in order to make maintenance easier.
 * 
 */
class Model {
    /**
     * Fetches all boards.
     * 
     * @return array 
     */
    static function getBoards(){
        $q = Config::getConnection()->query("SELECT * FROM `boards` ORDER BY `group` ASC, `shortname` ASC");
        $return = array();
        while($r = $q->fetch_assoc())
            $return[] = $r;
        return $return;
    }
    
    /**
     * Fetches board name and related info.
     * 
     * @param string $board The board shortname.
     * @return mixed array [shortname,longname,worksafe,pages,perpage], or FALSE on failure.
     */
    static function getBoardInfo($board){
        $board = Config::getConnection()->real_escape_string($board);
        $q = Config::getConnection()->query("SELECT * FROM `boards` WHERE `shortname`='$board'");
        if($q->num_rows > 0)
            $r = $q->fetch_assoc();
        else
            $r = false;
        return $r;
    }
    
    /**
     * Fetches a complete thread.
     * 
     * @param string $board The board shortname.
     * @param int $no The post id#.
     * @return array Mysqli result sets for thread, then post.
     */
    static function getThread($board,$no){
        $dbl = Config::getConnection();
        $board = $dbl->real_escape_string($board);
        $board = $board."_";
        $no = $dbl->real_escape_string($no);
        $threadQ = $dbl->query("SELECT * FROM `{$board}thread` WHERE `threadid`='$no'");
        $postQ = $dbl->query("SELECT * FROM `{$board}post` WHERE `threadid`='$no' ORDER BY `no` ASC");
        if(!($threadQ->num_rows > 0))
            throw new Exception ("Thread does not exist in the archive.");
        return array($threadQ,$postQ);
    }
    
    /**
     * Fetches a single post.
     * 
     * @param string $board The board shortname.
     * @param int $no The post id#.
     * @return mysqli_result Mysqli result set.
     */
    static function getPost($board,$no){
        $dbl = Config::getConnection();
        $board = $dbl->real_escape_string($board);
        $board = $board."_";
        $no = $dbl->real_escape_string($no);
        $postQ = $dbl->query("SELECT * FROM `{$board}post` WHERE `no`='$no'");
        if($postQ->num_rows === 0){
            throw new Exception("No such post $no exists in this archive");
        }
        return $postQ;
    }
    
    static function getPage($board,$page){
        $page--;
        $dbl = Config::getConnection();
        $prefix = $board->getName()."_";
        $perpage = $board->getThreadsPerPage();
        $pTable = $prefix."post";
        $tTable = $prefix."thread";
        $page = $dbl->real_escape_string($page);
        $number = $page*$perpage;
        $pageQuery = "SELECT {$tTable}.*, {$pTable}.*  FROM {$tTable} LEFT JOIN {$pTable} ON {$pTable}.no = {$tTable}.threadid WHERE {$tTable}.active = 1 ORDER BY ({$tTable}.sticky + {$tTable}.active) DESC, {$tTable}.lastreply DESC LIMIT $number,$perpage";
        $q = $dbl->query($pageQuery);
        return $q;
    }
    
    static function getCatalog($board){
        $dbl = Config::getConnection();
        $board = $dbl->real_escape_string($board);
        $prefix = $board."_";
        $pTable = $prefix."post";
        $tTable = $prefix."thread";
        $pageQuery = "SELECT {$tTable}.*, {$pTable}.*  FROM {$tTable} LEFT JOIN {$pTable} ON {$pTable}.no = {$tTable}.threadid WHERE {$tTable}.active = 1 ORDER BY {$tTable}.lastreply DESC";
        $q = $dbl->query($pageQuery);
        return $q;
    }
    
    static function getDeletedPosts($board){
        $dbl = Config::getConnection();
        $board = $dbl->real_escape_string($board);
        $prefix = $board."_";
        $pTable = $prefix."post";
        $query = "SELECT * FROM $pTable WHERE deleted=1";
        return $dbl->query($query);
    }
    
    static function getFileDeletedPosts($board){
        $dbl = Config::getConnection();
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
    static function getLastNPosts($board,$thread,$n){
        $dbl = Config::getConnection();
        $board = $dbl->real_escape_string($board);
        $threadId = $dbl->real_escape_string($thread);
        $n = abs((int)$n);
        $prefix = $board."_";
        $pTable = $prefix."post";
        $query = "SELECT * FROM $pTable WHERE threadid='$threadId' AND `threadid` <> `no` ORDER BY `no` DESC LIMIT 0,$n";
        $result = $dbl->query($query);
        $postArr = array();
        while($row = $result->fetch_assoc())
            $postArr[] = $row;
        return array_reverse($postArr);
    }
    
    /**
     * Get the last few deleted posts in case you mistakenly delete one from the archive.
     * 
     * @param Board|string $board
     */
    static function getLastNDeletedPosts($board,$n){
        $dbl = Config::getConnection();
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
     * Checks a username/password combo and returns a User object.
     * @param string $username
     * @param string $password
     * @return User|null A User object for the user, or null.
     */
    static function checkUsernamePasswordCombo($username,$password){
        $dbl = Config::getConnection();
        
        $username = $dbl->real_escape_string($username);
        $password = md5($password);
        
        $query = $dbl->query("SELECT * FROM `users` WHERE `username`='$username' AND `password_hash`=UNHEX('$password')");
        if($query->num_rows){
            $result = $query->fetch_assoc();
            $user = new User($result['uid'],$result['username'],$result['privilege'],$result['theme']);
        }
        else{
            $user = null;
        }
        
        return $user;
    }
    
    public static function getReports(){
        $dbl = Config::getConnection();
        $query = $dbl->query("SELECT *, COUNT(*) as count FROM `reports` GROUP BY `no` ORDER BY count DESC, time ASC");
        $ret = [];
        while($row = $query->fetch_assoc()){
            $ret[] = $row;
        }
        return $ret;
    }
    public static function getNumberOfReports(){
        $dbl = Config::getConnection();
        $data = $dbl->query("SELECT COUNT(*) as count FROM `reports`")->fetch_assoc();
        $ret = $data['count'];
        return $ret;
    }
    public static function ban($ip,$reason,$expires = 0){
        $dbl = Config::getConnectionRW();
        $ip = $dbl->real_escape_string($ip);
        $reason = $dbl->real_escape_string($reason);
        $expires = $dbl->real_escape_string($expires);
        $dbl->query("INSERT INTO `bans` (`ip`,`reason`,`expires`) VALUES ('$ip','$reason','$expires')");
    }
    public static function banned($ip){
        $dbl = Config::getConnection();
        $ip = $dbl->real_escape_string($ip);
        return $dbl->query("SELECT `ip` FROM `bans` WHERE `ip`='$ip'")->num_rows > 0;
    }
    public static function getBanInfo($ip){
        $dbl = Config::getConnection();
        $ip = $dbl->real_escape_string($ip);
        return $dbl->query("SELECT * FROM `bans` WHERE `ip`='$ip'")->fetch_assoc();
    }
    
    public static function deletePost($no,$board){
        $dbl = Config::getConnectionRW();
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
        $dbl = Config::getConnectionRW();
        $no = (int)$no;
        $board = $dbl->real_escape_string($board);
        $dbl->query("DELETE FROM `reports` WHERE `no`=$no AND `board`='$board'");
        if($dbl->errno){
            throw new Exception("Query failed.");
        }
    }
    public static function banReporter($no,$board){
        $dbl = Config::getConnectionRW();
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
        $dbl = Config::getConnectionRW();
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
        $dbl = Config::getConnectionRW();
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
        $query = Config::getConnection()->query("SELECT * FROM `users`");
        $ret = [];
        while($user = $query->fetch_assoc()){
            $ret[] = $user;
        }
        return $ret;
    }
    
    public static function addUser($username,$password,$privilege,$theme){
        $db = Config::getConnectionRW();
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
        $db = Config::getConnection();
        $query = "SELECT `users`.`username`,`users`.`uid`,`news`.`article_id`,`news`.`title`,`news`.`content`,`news`.`time`,`news`.`update` FROM `news` JOIN `users` ON `news`.`author_id`=`users`.`uid` WHERE `news`.`time` < UNIX_TIMESTAMP() ORDER BY `news`.`article_id` DESC";
        $q = $db->query($query);
        return $q->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getRequests($acceptedOnly = true){
        $db = Config::getConnection();
        $query = "SELECT * FROM `request`".$acceptedOnly?" WHERE `accepted`=0":"";
        $q = $db->query($query);
        return $q->fetch_all(MYSQLI_ASSOC);
    }
    public static function confirmRequest($ip){
        
    }
    public static function denyRequest($ip){
        
    }
}