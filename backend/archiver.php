<?php

/*
 * 4chan archival script
 * v5
 * by terrance
 * 
 * This thing is okay. It works sometimes. I run it using GNU screen.
 * 
 * Usage: php archiver.php -b board [-f]
 * 
 * -f : Log to file instead of to console
 * 
 * It will archive an entire standard imageboard.
 * I use a different script for /b/ because /b/ needs namesync.
 */
require_once '../inc/config.php';

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

error_reporting(E_ALL);

define("MAX_TIME", 200);

//Board config is stored in a flat file.
try {
  $boardObj = Model::get()->getBoard($board);
  define("EXEC_TIME", $boardObj->getArchiveTime());
} catch (Exception $ex) {
  die("Board $board has not been configured yet. Add it to the boards table." . PHP_EOL);
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
$pdo->exec(str_replace(['%BOARD%'], [$board], file_get_contents("newboard.sql")));

$lastTime = $boardObj->getLastCrawl();
/*
 * Begin Main loop
 */
while (!file_exists("$board.kill")) {
  try {
  $startTime = time();

  //Establish variables.
  $threadsToDownload = array();
  $everyThread = array();
  $postInsertArr = array();
  $downloadedThreadsTemp = array();

  //Getting important API stuffs.
  o("Downloading threads.json...");
  $threadsjson = json_decode(dlUrl("http://a.4cdn.org/{$board}/threads.json"), true);

  //Parse threads.json
  o("Parsing threads.json...");
  $highestTime = 0;
  foreach ($threadsjson as $page) {
    foreach ($page['threads'] as $thread) {
      $everyThread[] = $thread['no'];
      if ($thread['last_modified'] > $lastTime) {
        $threadsToDownload[] = $thread['no'];
      }
      if($thread['last_modified'] > $highestTime) {
        $highestTime = (int)$thread['last_modified'];
      }
    }
  }
  o("-Done: " . count($threadsToDownload) . " threads have changed.");

  if (count($threadsToDownload) == 0) {
    o("That's not enough!");
    goto wait;
  }

  o("Downloading catalog.json...");
  $catalog = json_decode(dlUrl("http://a.4cdn.org/{$board}/catalog.json"), true);

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
    $apiThread = json_decode(dlUrl("http://a.4cdn.org/{$board}/thread/$thread.json"), true);

    //Go through each reply.
    
    foreach ($apiThread["posts"] as $reply) {
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
    $pdo->query("UPDATE {$board}_thread SET `lastreply`='$last' WHERE `threadid`='$thread'");
  }

  /*
   * Update "Last updated" server var
   */
  o("Updating last update time: " . date("Y-m-d H:i:s"));
  $pdo->query("UPDATE `boards` SET `last_crawl`='" . $highestTime . "' WHERE `shortname`='$board'");
  
  $lastTime = $highestTime;
  } catch (Throwable $e) {
    o("***********************************");
    o("************   ERROR   ************");
    o("***********************************");
    o(" There has been an error reported:");
    o($e->getMessage(). " at line ".$e->getLine());
    o($e->getTraceAsString());
    o("Resetting database connection.");
    file_put_contents($board.".error", 
            date("c").PHP_EOL.$e->getMessage(). " at line ".$e->getLine().PHP_EOL.$e->getTraceAsString().PHP_EOL, FILE_APPEND);
    $pdo = null;
    Config::closePDOConnectionRW();
    $pdo = Config::getPDOConnectionRW();
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
