<?php

require_once '../inc/config.php';

use \Site\Archivers;
use \Model\Model;

$boards = Model::get()->getBoards(true);

$log = function($msg) {
  echo $msg.PHP_EOL;
  file_put_contents("check.log", $msg.PHP_EOL, FILE_APPEND);
};

$log("Checking at ".date('c'));

foreach($boards as $board)
{
  if( !$board->isArchive() )
    continue;
  $status = Archivers::getStatus($board->getName());
  switch($status) {
    case Archivers::STOPPED_UNCLEAN:
      Archivers::run($board->getName());
      $log("Restarted uncleanly stopped archiver for " . $board->getName() . ".");
      break;
    case Archivers::RUNNING:
      $log("Archiver for {$board->getName()} is running normally.");
      break;
    case Archivers::STOPPING:
      $log("Archiver for {$board->getName()} is stopping normally.");
      break;
    case Archivers::STOPPED:
      $log("Archiver for {$board->getName()} is stopped normally.");
      break;
  }
}