<?php
/*
 * Admin API functions:
 * 
 * Mostly RESTful
 * /admin/addUser
 *  - string username
 *  - string password
 *  - int privilege
 *  - string theme
 * /admin/deletePost/[board]/[no]
 * /admin/banImage/[hash]
 * /admin/deleteReport/[board]/[no]
 * /admin/banReporter/[board]/[no]
 * /admin/restorePost/[board]/[no]
 * /admin/fixDeleted
 * 
 * /admin/archiver/[board]/start
 * /admin/archiver/[board]/stop
 */
class AdminApi {
  public static function run(array $breadcrumbs):IPage {
    try {
      self::setHeaders();
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
  
  public static function addUser(array $path = []):array {
    if(OldModel::addUser(post('username'), post('password'), post('privilege'), post('theme'))) {
      return ['result'=>"User Added"];
    }
    throw new Exception("Could not add user");
  }
  
  public static function archiver(array $path):array {
    if(count($path) !== 5) {
      throw new Exception("Wrong number of parameters");
    }
    $board = Model::get()->getBoard(strtolower(alphanum($path[3])));
    switch(strtolower($path[4])) {
      case "start":
        Archivers::run($board->getName());
        sleep(1);
        return ['result'=>"Started"];
      case "stop":
        Archivers::stop($board->getName());
        return ['result'=>"Stopped"];
      default:
        throw new Exception("Invalid command");
    }
  }
  
  public static function banImage(array $path):array {
    
  }
  
  public static function banReporter(array $path):array {
    
  }
  
  public static function deletePost(array $path):array {
    
  }
  
  public static function deleteReport(array $path):array {
    
  }
  
  public static function restorePost(array $path):array {
    
  }
  
}
