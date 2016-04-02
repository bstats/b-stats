<?php
/*
 * Admin API functions:
 * 
 * Mostly RESTful
 * POST /admin/addBoard
 *  - string shortname
 *  - string longname
 *  - bool worksafe
 *  - int pages
 *  - int per_page
 *  - int privilege
 *  - bool swf_board
 *  - int group
 *  - bool hidden
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
 * GET  /admin/archiver/[board]/output
 * POST /admin/archiver/[board]/start
 * POST /admin/archiver/[board]/stop
 */
class AdminApi {
  public static function run(array $breadcrumbs):IPage {
    try {
      Site::requirePrivilege(Config::getCfg('permissions')['admin']);
      if(count($breadcrumbs) >= 3) {
        $method = strtolower(alphanum($breadcrumbs[2]));
        if($method != "run" && method_exists(self::class, $method)) {
          return new JsonPage(self::$method($breadcrumbs));
        }
      }
      throw new Exception("Api endpoint {$method} not found");
    }
    catch(Exception $e) {
      return new JsonPage(["error"=>$e->getMessage()]);
    }
  }
  
  public static function boards4chan(array $path):array {
    self::ensureGET();
    return json_decode(file_get_contents("https://a.4cdn.org/boards.json"), true);
  }
  
  public static function addBoard(array $path):array {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    try {
      Model::get()->getBoard(post('shortname'));
      return ['error'=>'Board exists'];
    } catch (Exception $ex) {
      Model::get()->addBoard(
              post('shortname'),
              post('longname'),
              post('worksafe'),
              post('pages'),
              post('per_page'),
              post('privilege'),
              post('swf_board'),
              post('group'),
              post('hidden'));
      Archivers::run(post('shortname'));
      return ['result'=>'Added'];
    }
  }
  
  public static function addUser(array $path = []):array {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    if(OldModel::addUser(post('username'), post('password'), post('privilege'), post('theme'))) {
      return ['result'=>"User Added"];
    }
    throw new Exception("Could not add user");
  }
  
  public static function archivers(array $path):array {
    self::ensureGET();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    $archivers = [];
    $boards = Model::get()->getBoards(true);
    foreach($boards as $board) {
      $archivers[] = [
        'board'=>$board->getName(),
        'status'=>Archivers::getStatus($board)];
    }
    return $archivers;
  }
  
  public static function archiver(array $path):array {
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    if(count($path) !== 5) {
      throw new Exception("Wrong number of parameters");
    }
    $board = Model::get()->getBoard(strtolower(alphanum($path[3])));
    switch(strtolower($path[4])) {
      case "start":
        self::ensurePOST();
        Archivers::run($board->getName());
        sleep(1);
        return ['result'=>"Started"];
      case "stop":
        self::ensurePOST();
        Archivers::stop($board->getName());
        return ['result'=>"Stopping"];
      case "output":
        self::ensureGET();
        return ['output'=>Archivers::getOutput($board->getName())];
      case "error":
        self::ensureGET();
        return ['output'=>Archivers::getError($board->getName())];
      default:
        throw new Exception("Invalid command");
    }
  }
  
  public static function boards(array $path):array {
    self::ensureGET();
    return Model::get()->getBoards(true);
  }
  
  public static function banImage(array $path):array {
    self::ensurePOST();
  }
  
  public static function banReporter(array $path):array {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
  }
  
  public static function deletePost(array $path):array {
    self::ensurePOST();
  }
  
  public static function deleteReport(array $path):array {
    self::ensurePOST();
  }
  
  public static function restorePost(array $path):array {
    self::ensurePOST();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
  }
  
  public static function configs(array $path):array {
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
  }
  
  public static function requests(array $path):array {
    self::ensureGET();
    Site::requirePrivilege(Config::getCfg('permissions')['owner']);
    return Model::get()->getRequests();
  }
  
  public static function ensurePOST(){
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405); // method not allowed
      throw new Exception("Method not allowed");
    }
  }
  
  public static function ensureGET(){
    if($_SERVER['REQUEST_METHOD'] !== 'GET') {
      http_response_code(405); // method not allowed
      throw new Exception("Method not allowed");
    }
  }
}
