<?php

/*
 * 4chan archival script
 * v5.2
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

/**
 * Checks that the keys exist in the array and if not throws an exception.
 * @param array $arr
 * @param array ...$key Keys to ensure the existence
 */
function require_keys(array $arr, ...$key)
{
  foreach($key as $k) {
    if(!array_key_exists($k, $arr)) {
      throw new RuntimeException("`$k` not set");
    }
  }
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
  $threadsToDownload = [];
  $everyThread = [];
  $postInsertArr = [];
  $downloadedThreadsTemp = [];

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
    goto wait;
  }

  //Reset active threads.
  o("Marking found threads as active...");
  $pdo->query("UPDATE `{$board}_thread` SET `active`='0' WHERE 1; ");
  $activeThreads = "(" . implode(",", $everyThread) . ")";
  $pdo->query("UPDATE `{$board}_thread` SET `active`='1' WHERE `threadid` IN $activeThreads");

  /*
   * Individual thread post loading
   */

  o("Downloading threads...");
  $i = 0;
  $queryNum = 0;
  $maxPerQuery = 50000;
  $downloadedThreads = array();
  $first = true;
  $firstThread = true;
  $postFields = [];
  $threadFields = [];
  $placeholders = [];
  $postFields[0] = [];
  $placeholders[0] = "";
  $threadInsertQuery = "INSERT INTO `{$board}_thread` "
      . "(`threadid`,`active`,`sticky`,`closed`,`archived`,`custom_spoiler`,"
      . "`replies`,`images`,`lastreply`,`last_crawl`) VALUES ";
  foreach ($threadsToDownload as $thread) {
    $apiThread = json_decode(dlUrl(PROTOCOL."://".API_DOMAIN."/{$board}/thread/$thread.json"), true);

    try {
      if(!is_array($apiThread)) {
        throw new Exception("Invalid response");
      }
      require_keys($apiThread, 'posts');
    } catch(Exception $ex) {
      log_error("Error: thread $thread 404'd ({$ex->getMessage()})");
      continue;
    }

    try {
      require_keys($apiThread['posts'][0], 'no', 'replies', 'images', 'time');
    } catch(Exception $ex) {
      log_error("Skipping thread $thread: {$ex->getMessage()}");
      continue;
    }

    array_push($threadFields,
        $apiThread['posts'][0]['no'],
        1,
        $apiThread['posts'][0]['sticky'] ?? 0,
        $apiThread['posts'][0]['closed'] ?? 0,
        $apiThread['posts'][0]['archived'] ?? 0,
        $apiThread['posts'][0]['custom_spoiler'] ?? null,
        $apiThread['posts'][0]['replies'],
        $apiThread['posts'][0]['images'],
        $apiThread['posts'][0]['time'],
        time());

    // Add thread details
    if(!$firstThread) {
      $threadInsertQuery .= ",(?,?,?,?,?,?,?,?,?,?)";
    } else {
      $firstThread = false;
      $threadInsertQuery .= "(?,?,?,?,?,?,?,?,?,?)";
    }

    //Go through each reply.
    foreach ($apiThread["posts"] as $reply) {
      try {
        require_keys($reply, 'no', 'resto', 'time');
      } catch(Exception $ex) {
        log_error("Skipping thread $thread: {$ex->getMessage()}");
        continue;
      }

      $i += 22;
      if($i > $maxPerQuery) {
        $i = 0;
        $queryNum++;
        $postFields[$queryNum] = [];
        $placeholders[$queryNum] = "";
        $first = true;
      }
      if(!$first) {
        $placeholders[$queryNum] .= ",(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      } else {
        $first = false;
        $placeholders[$queryNum] .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      }
      array_push($postFields[$queryNum],
            $reply['no'],
            $reply['resto'] == 0 ? $reply['no'] : $reply['resto'],
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
            $reply['tag'] ?? null,
            $reply['since4pass'] ?? null);
    }
    $downloadedThreads[] = $thread;
  }

  if(count($downloadedThreads) > 0) {
    $threadInsertQuery .= " ON DUPLICATE KEY UPDATE "
        . "`active`=1,`sticky`=VALUES(sticky),`closed`=VALUES(closed),`archived`=VALUES(archived),"
        . "`custom_spoiler`=VALUES(custom_spoiler),`replies`=VALUES(replies),"
        . "`images`=VALUES(images),"
        . "`last_crawl`=UNIX_TIMESTAMP()";
    o("Inserting thread infos...");
    $pdo->prepare($threadInsertQuery)->execute($threadFields);

    o("Marking deleted posts... ");
    foreach ($downloadedThreads as $key => $thread) {
      $downloadedThreadsTemp[$key] = "'$thread'";
    }
    $tempThreads = implode(",", $downloadedThreadsTemp);
    $pdo->query("UPDATE `{$board}_post` SET `deleted` = 1 WHERE `resto` IN ($tempThreads)");
    o("Inserting thread posts (and unmarking non-deleted)...");
    foreach ($postFields as $key => $value) {
      $postInsertQuery = "INSERT INTO `{$board}_post` "
          . "(`no`,`resto`,`time`,"
          . "`name`,`trip`,`email`,`sub`,`id`,`capcode`,`country`,`country_name`,`com`,"
          . "`tim`,`filename`,`ext`,`fsize`,`md5`,`w`,`h`,`filedeleted`,`spoiler`,`tag`,`since4pass`) VALUES "
          . $placeholders[$key]
          . " ON DUPLICATE KEY UPDATE "
          . "`com`=VALUES(com),`deleted`=0,`filedeleted`=VALUES(filedeleted),`since4pass`=VALUES(since4pass)";
      $pdo->prepare($postInsertQuery)->execute($postFields[$key]);
      o("Sent query $key");
    }
    o("Updating thread lastreply...");
    foreach ($downloadedThreads as $key => $thread) {
      $last = $pdo->query("SELECT MAX(`time`) AS `last`
     FROM {$board}_post
     WHERE resto = $thread
     GROUP BY resto")->fetchColumn(0);
      if ($last != '')
        $pdo->query("UPDATE {$board}_thread SET `lastreply`='$last' WHERE `threadid`='$thread'");
    }
  } else {
    log_error("No threads could be downloaded.");
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

    sleep(5);

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
    $sleepTime = (EXEC_TIME - (time() - $startTime));
    o("Waiting " . $sleepTime . " seconds..."
        .PHP_EOL."---------------------".PHP_EOL.PHP_EOL);
    while($sleepTime-- > 0) {
      if(file_exists("$board.kill")) goto kill;
      sleep(1);
    }
  }
}
kill:
o("Received kill request. Stopping.");
$pdo = null;
unlink($board . ".pid");
unlink($board . ".kill");
