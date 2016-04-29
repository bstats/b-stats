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
            if (file_exists(Site::getPath() . "/backend/$board.kill")) {
              return self::STOPPING;
            } else {
              return self::RUNNING;
            }
          }
        }
        return self::STOPPED_UNCLEAN;
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
      if (PHP_OS == "Linux") {
        $path = Site::getPath()."/backend/$board.buff";
        exec("screen -x $board -p0 -X hardcopy -h $path");
        sleep(1);
        $str = file_get_contents($path);
        unlink($path);
        return $str;
      } else {
        return file_get_contents(Site::getPath()."/backend/$board.log");
      }
    }
    return "";
  }
  
  /**
   * Gets the error history of the archiver.
   * 
   * @param type $board
   * @return string output history
   */
  static function getError(string $board):string {
    $path = Site::getPath()."/backend/$board.error";
    if(file_exists($path)) {
      return file_get_contents($path);
    }
    return "";
  }
  
  /**
   * Delete the error log for the given board
   * 
   * @param string $board board shortname
   */
  static function clearError(string $board) {
    $path = Site::getPath()."/backend/$board.error";
    if(file_exists($path)) {
      unlink($path);
    }
  }
  
  /**
   * 
   * @param type $board
   */
  static function getDetailedStatus(string $board):array {
    
  }

  static function run(string $board):bool {
    if (file_exists(Site::getPath() . "/backend/$board-archiver.php") 
            && !file_exists(Site::getPath() . "/backend/no_custom_archivers")) {
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        if (PHP_OS == "Linux") {
          exec("cd " . Site::getPath() . "/backend/ && screen -dmS $board php $board-archiver.php");
        } else {
          $path = Site::getPath() . "/backend/$board-archiver.php";
          $cmd = "c:/php/php.exe \"$path\" -f";
          pclose(popen('cd '.Site::getPath() . "/backend/".' && start /b '.$cmd,'r'));
        }
        return true;
      }
    } else {
      Model::get()->getBoard($board);
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        if (PHP_OS == "Linux") {
          exec("cd " . Site::getPath() . "/backend/ && " .
                  "screen -dmS $board php archiver.php -b $board");
        } else {
          $path = Site::getPath() . "/backend/archiver.php";
          $cmd = "c:/php/php.exe \"$path\" -b $board -f";
          pclose(popen('cd '.Site::getPath() . "/backend/".' && start /b '.$cmd,'r'));
        }
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
