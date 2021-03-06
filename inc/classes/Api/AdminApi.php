<?php
/*
 * Admin API functions:
 * 
 * Mostly RESTful
 * POST /admin/addBoard
 *  - string shortname
 *  - string longname
 *  - bool worksafe (optional, default false)
 *  - int pages (optional, default 10)
 *  - int per_page (optional, default 15)
 *  - int privilege (optional, default 0)
 *  - bool swf_board (optional, default false)
 *  - int group (optional, default 0)
 *  - bool hidden (optional, default false)
 *  - int archive_time (optional, default 60)
 *  - bool is_archive (optional, default true)
 * POST /admin/addUser
 *  - string username
 *  - string password
 *  - int privilege
 *  - string theme
 * 
 * GET  /admin/requests
 * 
 * POST /admin/deletePost/[board]/[no]
 * POST /admin/deleteReport/[board]/[no]
 * POST /admin/banImage/[hash]
 * POST /admin/banReporter/[board]/[no]
 * POST /admin/restorePost/[board]/[no]
 * 
 * GET  /admin/boards4chan
 * GET  /admin/boards
 * 
 * GET  /admin/archivers
 * GET  /admin/archiver/[board]/error
 * GET  /admin/archiver/[board]/output
 * POST /admin/archiver/[board]/start
 * POST /admin/archiver/[board]/stop
 * POST /admin/archiver/[board]/clearError
 *
 * Note: Once in backup mode, the site can only be put into
 * normal mode by accessing from localhost or deleting cfg/backup
 *
 * GET /admin/sitectl/enterBackupMode
 * GET /admin/sitectl/exitBackupMode
 *
 * On success:
 * { "result" : "Success [or something else" }
 * On error:
 * { "error" : "Some error message" }
 *
 * Basically, check if "error" is set...
 */
namespace Api;
use Site\Archivers;
use Site\Config;
use Exception;
use View\IPage;
use View\JsonPage;
use Model\Model;
use Model\OldModel;
use Site\Site;

class AdminApi
{
  public static function run(array $breadcrumbs):IPage
  {
    try {
      Site::requirePrivilege(Config::getCfg('permissions')['admin']);
      if (count($breadcrumbs) >= 3) {
        $method = strtolower(alphanum($breadcrumbs[2]));
        if ($method != "run" && method_exists(self::class, $method)) {
          return new JsonPage(self::$method($breadcrumbs));
        }
      }
      throw new Exception("Api endpoint {$method} not found");
    } catch (Exception $e) {
      return new JsonPage(["error" => $e->getMessage()]);
    }
  }

  public static function boards4chan(array $path):array
  {
    self::ensureGET();
    return json_decode(file_get_contents("https://a.4cdn.org/boards.json"), true);
  }

  public static function addBoard(array $path):array
  {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    try {
      Model::get()->getBoard(post('shortname'));
      return ['error' => 'Board exists'];
    } catch (Exception $ex) {
      $archive = post('is_archive', 1);
      Model::get()->addBoard(
          post('shortname'),
          post('longname'),
          (int)post('worksafe', 0),
          (int)post('pages', 10),
          (int)post('per_page', 15),
          (int)post('privilege', 0),
          (int)post('swf_board', 0),
          (int)post('group', 0),
          (int)post('hidden', 0),
          (int)post('archive_time', 60),
          (int)$archive);
      if ($archive) {
        Archivers::run(post('shortname'));
      }
      return ['result' => 'Added'];
    }
  }

  public static function addUser(array $path = []):array
  {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    if (OldModel::addUser(post('username'), post('password'), post('privilege'), post('theme'))) {
      return ['result' => "User Added"];
    }
    throw new Exception("Could not add user");
  }

  public static function archivers(array $path):array
  {
    self::ensureGET();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    $archivers = [];
    $boards = Model::get()->getBoards(true);
    foreach ($boards as $board) {
      if(!$board->isArchive()) continue;
      $archivers[] = [
          'board' => $board->getName(),
          'status' => Archivers::getStatus($board)];
    }
    return $archivers;
  }

  public static function archiver(array $path):array
  {
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    if (count($path) !== 5) {
      throw new Exception("Wrong number of parameters");
    }
    $board = Model::get()->getBoard(strtolower(alphanum($path[3])));
    switch (strtolower($path[4])) {
      case "start":
        self::ensurePOST();
        Archivers::run($board->getName());
        sleep(1);
        return ['result' => "Started"];
      case "stop":
        self::ensurePOST();
        Archivers::stop($board->getName());
        return ['result' => "Stopping"];
      case "output":
        self::ensureGET();
        return ['output' => Archivers::getOutput($board->getName())];
      case "error":
        self::ensureGET();
        return ['output' => Archivers::getError($board->getName())];
      case "clearerror":
        self::ensurePOST();
        Archivers::clearError($board->getName());
        return ['result' => 'success'];
      default:
        throw new Exception("Invalid command");
    }
  }

  public static function boards(array $path):array
  {
    self::ensureGET();
    return Model::get()->getBoards(true);
  }

  public static function banImage(array $path):array
  {
    self::ensurePOST();
    OldModel::banHash($path[3]);
    return ['result' => 'Success'];
  }

  public static function banReporter(array $path):array
  {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
  }

  public static function deletePost(array $path):array
  {
    self::ensurePOST();
  }

  public static function deleteReport(array $path):array
  {
    self::ensurePOST();
    Model::get()->archiveReport(Model::get()->getBoard($path[3]), $path[4]);
    return ["err"=>false,"errmsg"=>""];
  }

  public static function restorePost(array $path):array
  {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
  }

  public static function configs(array $path):array
  {
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
  }

  public static function requests(array $path):array
  {
    self::ensureGET();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    return Model::get()->getRequests();
  }

  public static function sitectl(array $path):array
  {
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    switch(strtolower($path[3]))
    {
      case 'enterbackupmode':
        Site::enterBackupMode();
        break;
      case 'exitbackupmode':
        Site::exitBackupMode();
        break;
    }
    return ['result'=>'success'];
  }

  public static function ensurePOST()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405); // method not allowed
      throw new Exception("Method not allowed");
    }
  }

  public static function ensureGET()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      http_response_code(405); // method not allowed
      throw new Exception("Method not allowed");
    }
  }
}
