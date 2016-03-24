<?php

/**
 * Archivers class
 * Holds static functions and constants relating to the starting/stopping
 * of archivers.
 * 
 * Note: will load [board]-archiver.php if it exists, otherwise loads
 * the generic "archiver.php" with proper command line arguments.
 * Still requires GNU screen.
 */
class Archivers {

  const STOPPED_UNCLEAN = -1;
  const STOPPED = 0;
  const RUNNING = 1;
  const STOPPING = 2;

  static function getStatus($board) {
    if (file_exists(Site::getPath() . "/backend/$board.pid")) {
      $pid = file_get_contents(Site::getPath() . "/backend/$board.pid");
      if (PHP_OS == "Linux") {
        if (file_exists("/proc/$pid")) {
          if (file_exists(Site::getPath() . "/backend/$board.kill")) {
            return self::STOPPING;
          } else {
            return self::RUNNING;
          }
        } else {
          return self::STOPPED_UNCLEAN;
        }
      } else { //Windows
        $running = false;
        $processes = explode("\n", shell_exec("tasklist.exe"));
        foreach ($processes as $process) {
          if (preg_match('/^(.*)\s+' . $pid . '/', $process)) {
            $running = true;
            break;
          }
        }
        if ($running == false) {
          return self::STOPPED_UNCLEAN;
        } else {
          return self::RUNNING;
        }
      }
    }
    return self::STOPPED;
  }

  static function run($board) {
    if (file_exists(Site::getPath() . "/backend/$board-archiver.php")) {
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        exec("cd " . Site::getPath() . "/backend/ && screen -dmS $board php $board-archiver.php");
        return true;
      }
    } else {
      $boardsjson = json_decode(file_get_contents(Site::getPath() . "/backend/boards.json"), true);
      if (key_exists($board, $boardsjson)) {
        if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
          $user = Config::getCfg('mysql')['read-write']["username"];
          $host = Config::getCfg('mysql')['read-write']["server"];
          $pass = Config::getCfg('mysql')['read-write']["password"];
          $db = Config::getCfg('mysql')['read-write']["db"];
          exec("cd " . Site::getPath() . "/backend/ && " .
                  "screen -dmS $board php archiver.php " .
                  "-b $board -u $user -p $pass -d $db -h $host");
          return true;
        }
      }
    }
    return false;
  }

  static function stop($board) {
    if (self::getStatus($board) == self::RUNNING) {
      touch(Site::getPath() . "/backend/$board.kill");
    }
  }

}
