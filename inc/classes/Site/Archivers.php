<?php
namespace Site;

use Model\Model;
use Site\Site;
use type;

/**
 * Archivers class
 * Holds static functions and constants relating to the starting/stopping
 * of archivers.
 *
 * Note: will load [board]-archiver.php if it exists, otherwise loads
 * the generic "archiver.php" with proper command line arguments.
 * Still requires GNU screen.
 */
class Archivers
{

  const STOPPED_UNCLEAN = "Stopped (unclean)";
  const STOPPED = "Stopped";
  const RUNNING = "Running";
  const STOPPING = "Stopping";

  static function getStatus(string $board):string
  {
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
   * @param string $board
   * @return string output history
   */
  static function getOutput(string $board):string
  {
    $status = self::getStatus($board);
    $logFile = Site::getPath() . "/backend/$board.log";
    if (file_exists($logFile)) {
      return file_get_contents($logFile);
    }
    return "";
  }

  /**
   * Gets the error history of the archiver.
   *
   * @param string $board
   * @return string output history
   */
  static function getError(string $board):string
  {
    $path = Site::getPath() . "/backend/$board.error";
    if (file_exists($path)) {
      return file_get_contents($path);
    }
    return "";
  }

  /**
   * Delete the error log for the given board
   *
   * @param string $board board shortname
   */
  static function clearError(string $board)
  {
    $path = Site::getPath() . "/backend/$board.error";
    if (file_exists($path)) {
      unlink($path);
    }
  }

  static function run(string $board):bool
  {
    if (file_exists(Site::getPath() . "/backend/$board-archiver.php")
        && !file_exists(Site::getPath() . "/backend/no_custom_archivers")
    ) {
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        if (PHP_OS == "Linux") {
          exec("cd " . Site::getPath() . "/backend/ && php $board-archiver.php -f >> /dev/null &");
        } else {
          $path = Site::getPath() . "/backend/$board-archiver.php";
          $cmd = "c:/php/php.exe \"$path\" -f";
          pclose(popen('cd ' . Site::getPath() . "/backend/" . ' && start /b ' . $cmd, 'r'));
        }
        return true;
      }
    } else {
      $b = Model::get()->getBoard($board);
      if (!$b->isArchive()) {
        return false;
      }
      if (self::getStatus($board) == self::STOPPED || self::getStatus($board) == self::STOPPED_UNCLEAN) {
        if (PHP_OS == "Linux") {
          exec("cd " . Site::getPath() . "/backend/ && " .
              "php archiver.php -b $board -f >> /dev/null &");
        } else {
          $path = Site::getPath() . "/backend/archiver.php";
          $cmd = "c:/php/php.exe \"$path\" -b $board -f";
          pclose(popen('cd ' . Site::getPath() . "/backend/" . ' && start /b ' . $cmd, 'r'));
        }
        return true;
      }
    }
    return false;
  }

  static function stop($board)
  {
    if (self::getStatus($board) == self::RUNNING) {
      touch(Site::getPath() . "/backend/$board.kill");
    }
  }

}
