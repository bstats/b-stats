<?php
/*
 * 4chan archival script
 * v4.0
 * by terrance
 * 
 * This thing is awful. It works sometimes. I run it using GNU screen.
 * 
 * Usage: php archiver.php -b board -u mysql_username -p mysql_password -d mysql_db
 * 
 * It will archive an entire standard imageboard.
 * I use a different script for /b/ and /f/ because /b/ needs namesync and
 * /f/ is an upload board which is quite different.
 */

if(php_sapi_name() != "cli"){
  die("This script must be run from the command line.".PHP_EOL);
}
else{
  if($argc < 9){
    failure:
    die("Usage: php archiver.php -b board -u mysql_username -p mysql_password -d mysql_db [-h mysql_host]".PHP_EOL);
  }
  $board = "";
  $mysql_username = "";
  $mysql_password = "";
  $mysql_db = "";
  $mysql_host = "localhost";
  for($i=1;$i<$argc;$i++){
    switch($argv[$i]){
      case "-b":
        $board = $argv[++$i];
        break;
      case "-u":
        $mysql_username = $argv[++$i];
        break;
      case "-p":
        $mysql_password = $argv[++$i];
        break;
      case "-d":
        $mysql_db = $argv[++$i];
        break;
      case "-h":
        $mysql_host = $argv[++$i];
        break;
    }
  }
  if($board == "" || $mysql_username == "" || $mysql_db == ""){
    goto failure;
  }
}

error_reporting(E_ALL);

define("EXEC_TIME",60);
define("MAX_TIME",200);

//Board config is stored in a flat file.
$cfg = json_decode(file_get_contents("boards.json"),true);
if(!isset($cfg[$board])){
  die("Board has not been configured yet. Add it to boards.json.".PHP_EOL);
}
$boardname = $cfg[$board]['title'];
$pages = $cfg[$board]['pages'];
$perpage = $cfg[$board]['per_page'];
$worksafe = $cfg[$board]['ws_board'];
$group = $cfg[$board]['group'];


file_put_contents("$board.pid", getmypid());
unlink("$board.kill");
$startTime = time();

function o($msg,$newline = true){
    global $startTime;
    echo (time() - $startTime)." : ".$msg.($newline?"\n":"");
    flush();
}

function dlUrl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

    o("Connecting...");
$dbl = new mysqli($mysql_host,$mysql_username,$mysql_password,$mysql_db);
$dbl->set_charset("utf8");
    o("-Done.");

    o("Setting up DB");
$dbl->query(file_get_contents("boards.sql"));

$dbl->query("INSERT INTO `boards`"
      ."(`shortname`,`longname`,`worksafe`,`pages`,`perpage`,`first_crawl`,`group`)"
      ."VALUES ('$board','$boardname','$worksafe','$pages','$perpage',UNIX_TIMESTAMP(),'$group') "
      ."ON DUPLICATE KEY UPDATE `shortname`=VALUES(`shortname`)");

$dbl->query("CREATE TABLE IF NOT EXISTS `{$board}_post` (
  `no` int(13) NOT NULL,
  `threadid` int(13) NOT NULL,
  `time` int(13) NOT NULL,
  `tim` bigint(13) NOT NULL,
  `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `email` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `subject` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `trip` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `capcode` enum('','admin','mod','developer','admin_highlight') COLLATE utf8_unicode_ci NOT NULL,
  `md5` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `filename` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `ext` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `fsize` int(10) NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `w` int(5) NOT NULL,
  `h` int(5) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `file_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`no`),
  KEY `threadid` (`threadid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

$dbl->query("CREATE TABLE IF NOT EXISTS `{$board}_deleted` (
  `no` int(13) NOT NULL,
  `threadid` int(13) NOT NULL,
  `time` int(13) NOT NULL,
  `tim` bigint(13) NOT NULL,
  `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `email` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `subject` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `trip` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `capcode` enum('','admin','mod','developer','admin_highlight') COLLATE utf8_unicode_ci NOT NULL,
  `md5` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `filename` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `ext` enum('','.jpg','.png','.gif','.bmp','.swf','.pdf') COLLATE utf8_unicode_ci NOT NULL,
  `fsize` int(10) NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `w` int(5) NOT NULL,
  `h` int(5) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `file_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`no`),
  KEY `threadid` (`threadid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

$dbl->query("CREATE TABLE IF NOT EXISTS `{$board}_thread` (
  `threadid` int(15) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `replies` int(15) NOT NULL,
  `images` int(15) NOT NULL,
  `firstreply` int(15) NOT NULL,
  `lastreply` int(15) NOT NULL,
  `lastcrawl` int(15) UNSIGNED NOT NULL DEFAULT '0',
  `sticky` tinyint(1) NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`threadid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$dbl->query("CREATE TABLE IF NOT EXISTS `{$board}_comment` (
  `no` int(11) NOT NULL,
  `comment_plaintext` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`no`),
  FULLTEXT KEY `comment_plaintext` (`comment_plaintext`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
    o("-Done.");
    
$lastTime = (int)($dbl->query("SELECT `last_crawl` FROM `boards` WHERE `shortname`='$board'")->fetch_array()[0]);

/*
 * Begin Main loop
 */
while(!file_exists("$board.kill")){
    $startTime = time();
    
    //Establish variables.
    $picsToDL = array();
    $threadsToDownload = array();
    $everyThread = array();
    $postInsertArr = array();
    $downloadedThreadsTemp = array();

    //Getting important API stuffs.
        o("Downloading threads.json...");
    $threadsjson = json_decode(dlUrl("http://a.4cdn.org/{$board}/threads.json"),true);
        o("-Done.");
    
    //Parse threads.json
        o("Parsing threads.json...");
    foreach($threadsjson as $page){
        foreach($page['threads'] as $thread){
            $everyThread[] = $thread['no'];
            if($thread['last_modified'] > $lastTime)
                $threadsToDownload[] = $thread['no'];
        }
    }
      o("-Done: ".count($threadsToDownload)." threads have changed.");
      
    if(count($threadsToDownload) == 0){
      o("That's not enough!");
      goto wait;
    }
    
      o("Downloading catalog.json...");
    $catalog = json_decode(dlUrl("http://a.4cdn.org/{$board}/catalog.json"),true);
      o("Done!");
      
    //Reset active threads.
      o("Updating active threads...");
    $dbl->query("UPDATE `{$board}_thread` SET `active`='0' WHERE 1; ");
    $activethreadindic = "(".implode(",",$everyThread).")";
    $dbl->query("UPDATE `{$board}_thread` SET `active`='1' WHERE `threadid` IN $activethreadindic");
      o("-Done.");
        

    
    //Parse 4chan Catalog
        o("Parsing catalog.json...");
    foreach($catalog as $page){
        foreach($page['threads'] as $thread){
            if(in_array($thread['no'],$threadsToDownload))
                $postInsertArr[] = $thread;
        }
    }
        o("-Done: ".count($postInsertArr)." OPs to be inserted.");
        
    if(count($postInsertArr) == 0){
        o("That's not enough! Waiting ".EXEC_TIME." seconds...\n\n");
        sleep(EXEC_TIME);
        continue;
    }
        o("Preparing insert of OPs...");
    $i=0;
    $threadInsertQuery = "INSERT INTO `{$board}_thread` (`threadid`,`active`,`replies`,`images`,`firstreply`,`sticky`,`closed`) VALUES ";
    $postInsertQuery = "INSERT INTO `{$board}_post` (`no`,`threadid`,`time`,`tim`,`name`,`trip`,`subject`,`email`,`capcode`,`md5`,`filename`,`ext`,`comment`,`w`,`h`,`fsize`,`file_deleted`) VALUES ";
    foreach($postInsertArr as $thread) {
        $threadId = $dbl->real_escape_string($thread['no']);
        $time =     $dbl->real_escape_string($thread['time']);
        $name =     $dbl->real_escape_string(isset($thread['name']) ? $thread['name'] : "");
        $trip =     $dbl->real_escape_string(isset($thread['trip']) ? $thread['trip'] : "");
        $subject =  $dbl->real_escape_string(isset($thread['sub']) ? $thread['sub'] : "");
        $email =    $dbl->real_escape_string(isset($thread['email']) ? $thread['email'] : "");
        
        $filename = $dbl->real_escape_string(isset($thread['filename']) ? $thread['filename'] : "");
        $ext =      $dbl->real_escape_string(isset($thread['ext']) ? $thread['ext'] : "");
        $md5 =      $dbl->real_escape_string(isset($thread['md5']) ? $thread['md5'] : "");
        $w =        $dbl->real_escape_string(isset($thread['w']) ? $thread['w'] : "");
        $h =        $dbl->real_escape_string(isset($thread['h']) ? $thread['h'] : "");
        $fsize =    $dbl->real_escape_string(isset($thread['fsize']) ? $thread['fsize'] : "");
        $tim =      $dbl->real_escape_string(isset($thread['tim']) ? $thread['tim'] : "");
        
        $comment =  $dbl->real_escape_string(isset($thread['com']) ? $thread['com'] : "");
        $capcode =  $dbl->real_escape_string(isset($thread['capcode']) ? $thread['capcode'] : "");
        
        $file_deleted =  $dbl->real_escape_string(isset($thread['file_deleted']) ? $thread['file_deleted'] : "");
        
        $replies =  $dbl->real_escape_string($thread ['replies']);
        $images =   $dbl->real_escape_string($thread ['images']);
        $sticky =   $dbl->real_escape_string(isset($thread['sticky']) ? $thread['sticky'] : "");
        $closed =   $dbl->real_escape_string(isset($thread['closed']) ? $thread['closed'] : "");
        if($i++ > 0){ $threadInsertQuery .= ","; $postInsertQuery .= ","; }
        $threadInsertQuery .= "('$threadId','1','$replies','$images','$time','$sticky','$closed')";
        $postInsertQuery .= "('$threadId','$threadId','$time','$tim','$name','$trip','$subject','$email','$capcode','$md5','$filename','$ext','$comment','$w','$h','$fsize','$file_deleted')";
    }
    $threadInsertQuery .= " ON DUPLICATE KEY UPDATE `active`=1,`replies`=VALUES(replies),`images`=VALUES(images),`sticky`=VALUES(sticky),`closed`=VALUES(closed)";
    $postInsertQuery .= " ON DUPLICATE KEY UPDATE `comment`=VALUES(comment), `deleted`=0,`file_deleted`=VALUES(file_deleted)";
        o("-Done.");

        o("Inserting thread details (".count($threadsToDownload).")");
    $dbl->query($threadInsertQuery);
        o("-Done.");
        o("Inserting OP Posts (".count($postInsertArr).")");

    $dbl->query($postInsertQuery);
        o("-Done.");

    $postInsertQuery = "INSERT INTO `{$board}_post` ".
                       "(`no`,`threadid`,`time`,`tim`,`name`,`email`,`subject`,`trip`,`capcode`,`md5`,`filename`,`ext`,`fsize`,`comment`,`w`,`h`,`deleted`,`file_deleted`) ".
                       "VALUES ";
        o("Downloading threads...");
    $i=0;
    $downloadedThreads = array();
    foreach($threadsToDownload as $thread){
        $apiThread = json_decode(dlUrl("http://a.4cdn.org/{$board}/thread/$thread.json"),true);

        //Go through each reply.
        foreach($apiThread["posts"] as $reply){
            $no       = $dbl->real_escape_string($reply['no']);
            $threadid = $dbl->real_escape_string($thread);
            $time     = $dbl->real_escape_string($reply['time']);
            $tim      = $dbl->real_escape_string(isset($reply['tim']) ? $reply['tim'] : '');
            $name     = $dbl->real_escape_string(isset($reply['name']) ? $reply['name'] : '');
            $trip     = $dbl->real_escape_string(isset($reply['trip']) ? $reply['trip'] : '');
            $subject  = $dbl->real_escape_string(isset($reply['sub']) ? $reply['sub'] : '');
            $email    = $dbl->real_escape_string(isset($reply['email']) ? $reply['email'] : '');
            $capcode  = $dbl->real_escape_string(isset($reply['capcode']) ? $reply['capcode'] : '');
            $md5      = $dbl->real_escape_string(isset($reply['md5']) ? $reply['md5'] : '');
            $filename = $dbl->real_escape_string(isset($reply['filename']) ? $reply['filename'] : '');
            $ext      = $dbl->real_escape_string(isset($reply['ext']) ? $reply['ext'] : '');
            $tag      = $dbl->real_escape_string(isset($reply['tag']) ? $reply['tag'] : '');
            $fsize    = $dbl->real_escape_string(isset($reply['fsize']) ? $reply['fsize'] : '');
            $comment  = $dbl->real_escape_string(isset($reply['com']) ? $reply['com'] : '');
            $w        = $dbl->real_escape_string(isset($reply['w']) ? $reply['w'] : 0);
            $h        = $dbl->real_escape_string(isset($reply['h']) ? $reply['h'] : 0);
            
            $file_deleted =  $dbl->real_escape_string(isset($reply['file_deleted']) ? $reply['file_deleted'] : '');
            
            if($i++ > 0){ $postInsertQuery .= ",\n"; }
            $postInsertQuery .= 
                    "('$no','$threadid','$time','$tim','$name','$email','$subject','$trip','$capcode','$md5','$filename','$ext','$fsize','$comment','$w','$h',0,'$file_deleted')";

            if (isset($reply['md5'])) {
                $picsToDL[] = [ "md5" => $reply['md5'],
                                "tim" => $reply['tim'],
                                "filename" => $reply['filename'],
                                "ext" => $reply['ext'],
                                "fsize" => $reply['fsize'],
                                "w" => $reply['w'],
                                "h" => $reply['h']];
            }
        }
        $downloadedThreads[] = $thread;
        echo ".";
    }
    echo PHP_EOL;
        o("-Done.");

        o("Marking deleted posts... ");
    foreach($downloadedThreads as $key=>$thread){
        $downloadedThreadsTemp[$key] = "'$thread'";
    }
    $tempThreads = implode(",", $downloadedThreadsTemp);
        o("Sending query...");
        o($tempThreads);
    $dbl->query("UPDATE `{$board}_post` SET `deleted` = 1 WHERE `threadid` IN ($tempThreads)");
        o("-Done.");
    $postInsertQuery .= " ON DUPLICATE KEY UPDATE `comment`=VALUES(`comment`),`deleted`='0',`file_deleted`=VALUES(file_deleted),`subject`=VALUES(`subject`)";
        o("Inserting threads...");
        o("Query: ".strlen($postInsertQuery));
    $dbl->query($postInsertQuery);
        o("-Done.");
        
        o("Updating thread lastreply...");
    foreach($downloadedThreads as $key=>$thread){
        list($result) = $dbl->query("SELECT 
         MAX(time) AS lastreply
         FROM {$board}_post
         WHERE threadid = $thread
         GROUP BY threadid")->fetch_array();
        $dbl->query("UPDATE {$board}_thread SET lastreply='$result' WHERE `threadid`='$thread'");
    }
        
        o("Writing imagelist...");
    file_put_contents("{$board}_media.json",json_encode($picsToDL));
        o("-Done.");
    
    
    /*
     * Update "Last updated" server var
     */
        o("Updating last update time: ".date("Y-m-d H:i:s"));
    $dbl->query("UPDATE `boards` SET `last_crawl`='".time()."' WHERE `shortname`='$board'");
        o("-Done.");
        
    if((time() - $startTime) < EXEC_TIME){
      wait:
        o("Waiting ".(EXEC_TIME - (time() - $startTime))." seconds...");
        echo "---------------------\n\n";
        sleep(EXEC_TIME - (time() - $startTime));
    }
    $lastTime = $startTime;
}

o ("Received kill request. Stopping.");
$dbl->close();
unlink($board.".pid");
unlink($board.".kill");
