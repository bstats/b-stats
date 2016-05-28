<?php

/*
 * 4chan archival script
 * v5.1
 * by terrance
 * 
 * This thing is okay. It works sometimes.
 * 
 * Usage: php archiver.php -b board [-f]
 * 
 * -f : Log to file instead of to console
 * 
 * It will archive an entire standard imageboard.
 * I use a different script for /b/ because /b/ needs namesync.
 */

require_once '../inc/config.php';

use Model\Model;
use Site\Config;

define('PROTOCOL','http');
define('API_DOMAIN',"a.4cdn.org");

if (php_sapi_name() != "cli") {
  die("This script must be run from the command line." . PHP_EOL);
} else {
  if ($argc < 3) {
    failure:
    die("Usage: php archiver.php -b board [-f]" . PHP_EOL);
  }
  $board = "";
  $logToFile = false;
  for ($i = 1; $i < $argc; $i++) {
    switch ($argv[$i]) {
      case "-b":
        $board = $argv[++$i];
        break;
      case "-f":
        $logToFile = true;
        break;
    }
  }
  if ($board == "") {
    goto failure;
  }
}

/**
 * Logs an error message to the error log. A newline character is appended to the message.
 * The given message is also echoed to the console.
 *
 * @param $message string the message to log.
 */
function log_error(string $message){
  global $board;
  o($message);
  file_put_contents($board.'.error', date("c: ").$message.PHP_EOL, FILE_APPEND);
}

function log_exception(Throwable $ex) {
  log_error("\tException: ".$ex->getMessage().PHP_EOL.
            "At ".$ex->getFile().":".$ex->getLine());
}


error_reporting(E_ALL);

define("MAX_TIME", 200);

//Board config is stored in the database.
try {
  $boardObj = Model::get()->getBoard($board);
  define("EXEC_TIME", $boardObj->getArchiveTime());
} catch (Exception $ex) {
  log_error("Board has not been configured. Add it to the boards table.");
  log_exception($ex);
  die();
}


file_put_contents("$board.pid", getmypid());
if (file_exists("$board.kill")) {
  unlink("$board.kill");
}
$startTime = time();

function o($msg, $newline = true) {
  global $startTime, $logToFile, $board;
  $line = (time() - $startTime) . ' : ' . $msg . ($newline ? PHP_EOL : '');
  if($logToFile) {
    file_put_contents($board.'.log', $line, FILE_APPEND);
  } else {
    echo $line;
  }
  
  flush();
}

$ch = curl_init();
function dlUrl($url) {
  global $ch;
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);
  $data = curl_exec($ch);
  return $data;
}

o("Connecting to DB...");
/** @var PDO */
$pdo = Config::getPDOConnectionRW();

o("Setting up DB...");
$pdo->exec(str_replace(['%BOARD%'], [$board], file_get_contents("../sql/newboard.sql")));

$lastTime = $boardObj->getLastCrawl();
/*
 * Begin Main loop
 */
while (!file_exists("$board.kill")) {
  try {
  if($logToFile) {
    // Clear output file
    fclose(fopen($board.'.log', 'w'));
  }

  $startTime = time();

  //Establish variables.
  $threadsToDownload = array();
  $everyThread = array();
  $postInsertArr = array();
  $downloadedThreadsTemp = array();

  //Getting important API stuffs.
  o("Downloading threads.json...");
  $threadsjson = json_decode(dlUrl(PROTOCOL."://".API_DOMAIN."/{$board}/threads.json"), true);
  if(!is_array($threadsjson)) {
    log_error("Error downloading threads.json");
    goto wait;
  }
  //Parse threads.json
  o("Parsing threads.json...");
  $highestTime = 0;
  foreach ($threadsjson as $page) {
    foreach ($page['threads'] as $thread) {
      $everyThread[] = $thread['no'];
      if ($thread['last_modified'] >= $lastTime) {
        $threadsToDownload[] = $thread['no'];
      }
      if($thread['last_modified'] > $highestTime) {
        $highestTime = (int)$thread['last_modified'];
      }
    }
  }
  o("-Done: " . count($threadsToDownload) . " threads have changed.");

  if (count($threadsToDownload) == 0) {
    o("That's not enough: last time was $lastTime, highest thread update time was $highestTime");
    o("That's not enough!");
    goto wait;
  }

  o("Downloading catalog.json...");
  $catalog = json_decode(dlUrl(PROTOCOL."://".API_DOMAIN."/{$board}/catalog.json"), true);

  //Reset active threads.
  o("Marking found threads as active...");
  $pdo->query("UPDATE `{$board}_thread` SET `active`='0' WHERE 1; ");
  $activethreadindic = "(" . implode(",", $everyThread) . ")";
  $pdo->query("UPDATE `{$board}_thread` SET `active`='1' WHERE `threadid` IN $activethreadindic");



  //Parse 4chan Catalog
  o("Parsing catalog.json...");
  foreach ($catalog as $page) {
    foreach ($page['threads'] as $thread) {
      if (in_array($thread['no'], $threadsToDownload))
        $postInsertArr[] = $thread;
    }
  }
  o("-Done: " . count($postInsertArr) . " OPs to be inserted.");

  if (count($postInsertArr) == 0) {
    o("That's not enough! Waiting " . EXEC_TIME . " seconds...\n\n");
    sleep(EXEC_TIME);
    continue;
  }
  
  
  /*
   * Board index loading (OPs)
   */
  
  o("Preparing to insert OPs...");
  $i = 0;
  $threadInsertQuery = "INSERT INTO `{$board}_thread` "
          . "(`threadid`,`active`,`sticky`,`closed`,`archived`,`custom_spoiler`,"
          . "`replies`,`images`,`lastreply`,`last_crawl`) VALUES ";
          
  
  $postInsertQuery = "INSERT INTO `{$board}_post` "
          . "(`no`,`resto`,`time`,"
          . "`name`,`trip`,`email`,`sub`,`id`,`capcode`,`country`,`country_name`,`com`,"
          . "`tim`,`filename`,`ext`,`fsize`,`md5`,`w`,`h`,`filedeleted`,`spoiler`,`tag`) VALUES ";

  $threadFields = [];
  $postFields = [];
  $curTime = time();
  $first = true;
  foreach ($postInsertArr as $thread) {
    if(!$first) {
      $threadInsertQuery .= ",(?,?,?,?,?,?,?,?,?,?)";
      $postInsertQuery .= ",(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    } else {
      $first = false;
      $threadInsertQuery .= "(?,?,?,?,?,?,?,?,?,?)";
      $postInsertQuery .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    }
    array_push($threadFields, 
            $thread['no'],
            1,
            $thread['sticky'] ?? 0,
            $thread['closed'] ?? 0,
            $thread['archived'] ?? 0,
            $thread['custom_spoiler'] ?? null,
            $thread['replies'],
            $thread['images'],
            $thread['time'],
            $curTime);
    array_push($postFields,
            $thread['no'],
            $thread['no'],
            $thread['time'],
            $thread['name'] ?? null,
            $thread['trip'] ?? null,
            $thread['email'] ?? null,
            $thread['sub'] ?? null,
            $thread['id'] ?? null,
            $thread['capcode'] ?? null,
            $thread['country'] ?? null,
            $thread['country_name'] ?? null,
            $thread['com'] ?? null,
            $thread['tim'] ?? null,
            $thread['filename'] ?? null,
            $thread['ext'] ?? null,
            $thread['fsize'] ?? null,
            isset($thread['md5']) ? base64_decode($thread['md5']) : null,
            $thread['w'] ?? null,
            $thread['h'] ?? null,
            $thread['filedeleted'] ?? null,
            $thread['spoiler'] ?? null,
            $thread['tag'] ?? null);
  }
  $threadInsertQuery .= " ON DUPLICATE KEY UPDATE "
          . "`active`=1,`sticky`=VALUES(sticky),`closed`=VALUES(closed),`archived`=VALUES(archived),"
          . "`custom_spoiler`=VALUES(custom_spoiler),`replies`=VALUES(replies),"
          . "`images`=VALUES(images),"
          . "`last_crawl`=UNIX_TIMESTAMP()";
  $postInsertQuery .= " ON DUPLICATE KEY UPDATE "
          . "`com`=VALUES(com),`deleted`=0,`filedeleted`=VALUES(filedeleted)";
  o("Inserting thread infos...");
  $pdo->prepare($threadInsertQuery)->execute($threadFields);
  o("Inserting post infos...");
  $pdo->prepare($postInsertQuery)->execute($postFields);
  
  /*
   * Individual thread post loading
   */

  o("Downloading threads...");
  $i = 0;
  $queryNum = 0;
  $maxPerQuery = 50000;
  $downloadedThreads = array();
  $first = true;
  $postFields = [];
  $placeholders = [];
  $postFields[0] = [];
  $placeholders[0] = "";
  foreach ($threadsToDownload as $thread) {
    $apiThread = json_decode(dlUrl(PROTOCOL."://".API_DOMAIN."/{$board}/thread/$thread.json"), true);

    if(!isset($apiThread['posts'])) {
      log_error("Error: thread $thread 404'd.");
      continue;
    }

    //Go through each reply.
    foreach ($apiThread["posts"] as $reply) {
      if(!isset($reply['no'])) {
        log_error("Error: required variable `no` not set in a post in thread $thread, skipping");
        continue;
      } else if(!isset($reply['resto'])) {
        log_error("Error: required variable `resto` not set in a post in thread $thread, skipping");
        continue;
      } else if(!isset($reply['time'])) {
        log_error("Error: required variable `time` not set in a post in thread $thread, skipping");
        continue;
      }
      $i += 21;
      if($i > $maxPerQuery) {
        $i = 0;
        $queryNum++;
        $postFields[$queryNum] = [];
        $placeholders[$queryNum] = "";
        $first = true;
      }
      if(!$first) {
        $placeholders[$queryNum] .= ",(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      } else {
        $first = false;
        $placeholders[$queryNum] .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      }
      array_push($postFields[$queryNum],
            $reply['no'],
            $reply['resto'],
            $reply['time'],
            $reply['name'] ?? null,
            $reply['trip'] ?? null,
            $reply['email'] ?? null,
            $reply['sub'] ?? null,
            $reply['id'] ?? null,
            $reply['capcode'] ?? null,
            $reply['country'] ?? null,
            $reply['country_name'] ?? null,
            $reply['com'] ?? null,
            $reply['tim'] ?? null,
            $reply['filename'] ?? null,
            $reply['ext'] ?? null,
            $reply['fsize'] ?? null,
            isset($reply['md5']) ? base64_decode($reply['md5']) : null,
            $reply['w'] ?? null,
            $reply['h'] ?? null,
            $reply['filedeleted'] ?? null,
            $reply['spoiler'] ?? null,
            $reply['tag'] ?? null);
    }
    $downloadedThreads[] = $thread;
  }

  o("Marking deleted posts... ");
  foreach ($downloadedThreads as $key => $thread) {
    $downloadedThreadsTemp[$key] = "'$thread'";
  }
  $tempThreads = implode(",", $downloadedThreadsTemp);
  $pdo->query("UPDATE `{$board}_post` SET `deleted` = 1 WHERE `resto` IN ($tempThreads)");
  o("Inserting threads (and unmarking non-deleted)...");
  foreach($postFields as $key=>$value) {
    $postInsertQuery = "INSERT INTO `{$board}_post` "
        . "(`no`,`resto`,`time`,"
        . "`name`,`trip`,`email`,`sub`,`id`,`capcode`,`country`,`country_name`,`com`,"
        . "`tim`,`filename`,`ext`,`fsize`,`md5`,`w`,`h`,`filedeleted`,`spoiler`,`tag`) VALUES "
        . $placeholders[$key]
        . " ON DUPLICATE KEY UPDATE "
        . "`com`=VALUES(com),`deleted`=0,`filedeleted`=VALUES(filedeleted)";
    $pdo->prepare($postInsertQuery)->execute($postFields[$key]);
    o("Sent query $key");
  }
  o("Updating thread lastreply...");
  foreach($downloadedThreads as $key=>$thread){
    $last = $pdo->query("SELECT MAX(`time`) AS `last`
     FROM {$board}_post
     WHERE resto = $thread
     GROUP BY resto")->fetchColumn(0);
    if($last != '')
      $pdo->query("UPDATE {$board}_thread SET `lastreply`='$last' WHERE `threadid`='$thread'");
  }

  /*
   * Update "Last updated" server var
   */
  o("Updating last update time: " . date("Y-m-d H:i:s"));
  $pdo->query("UPDATE `boards` SET `last_crawl`='" . $highestTime . "' WHERE `shortname`='$board'");
  
  $lastTime = $highestTime;
  } catch (Throwable $e) {
    log_exception($e);
    o("Restarting script...");
    
    $pdo = null;
    Config::closePDOConnectionRW();
    if(PHP_OS != "WINNT") {
      // spawn a new process
      if(!pcntl_fork())
        pcntl_exec(PHP_BINARY, $argv);
      die;
    } else {
      $args = implode(' ', $argv);
      exec("psexec -d -accepteula C:\\php\\php.exe $args");
      die;
    }
  }
  if ((time() - $startTime) < EXEC_TIME) {
    wait:
    o("Waiting " . (EXEC_TIME - (time() - $startTime)) . " seconds..."
        .PHP_EOL."---------------------".PHP_EOL.PHP_EOL);
    sleep(EXEC_TIME - (time() - $startTime));
  }
}

o("Received kill request. Stopping.");
$pdo = null;
unlink($board . ".pid");
unlink($board . ".kill");
