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

  const STOPPED_UNCLEAN = "Stopped (unclean)";
  const STOPPED = "Stopped";
  const RUNNING = "Running";
  const STOPPING = "Stopping";

  static function getStatus(string $board):string {
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
  
  /**
   * Gets the output history of the archiver from screen.
   * 
   * @param type $board
   * @return string output history
   */
  static function getOutput(string $board):string {
    $status = self::getStatus($board);
    if($status == "Running" || $status == "Stopping") {
      $path = Site::getPath()."/backend/$board.buff";
      exec("screen -x $board -p0 -X hardcopy -h $path");
      sleep(1);
      $str = file_get_contents($path);
      unlink($path);
      return $str;
    }
    return "";
  }
  
  /**
   * 
   * @param type $board
   */
  static function getDetailedStatus(string $board):array {
    
  }

  static function run(string $board):bool {
    if (file_exists(Site::getPath() . "/backend/$board-archiver.php")) {
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        exec("cd " . Site::getPath() . "/backend/ && screen -dmS $board php $board-archiver.php");
        return true;
      }
    } else {
      Model::get()->getBoard($board);
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        exec("cd " . Site::getPath() . "/backend/ && " .
                "screen -dmS $board php archiver.php -b $board");
        return true;
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
