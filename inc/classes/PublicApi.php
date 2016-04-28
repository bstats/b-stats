<?php
/*
 * Plain RESTful API endpoints (currently very volatile):
 * 
 * API is case-insensitive. Only GET is supported (for now?)
 * 
 * /api/bannedHashes
 *  - array of banned image hashes
 * 
 * /api/boards
 *  - array of board objects
 * 
 * /api/board/[board-shortname]
 *  - board object for the given shortname
 * 
 * /api/board/[board-shortname]/activeMedia
 *  - array of active media on the board
 * 
 * /api/thread/[board-shortname]/[thread-id]
 *  - thread metadata object
 * 
 * /api/thread/[board-shortname]/[thread-id]/posts
 *  - object with thread metadata and array of thread posts
 * 
 * /api/post/[board-shortname]/[post-id]
 *  - post object
 * 
 * /api/styles
 * - list of styles
 * 
 * If something went wrong, returns:
 * { "error" : "Error message" }
 */
class PublicApi {
  public static function run(array $breadcrumbs):IPage {
    try {
      if($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("HTTP request method not supported");
      }
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
  
  public static function bannedHashes(array $path):array {
    return Model::get()->getBannedHashes();
  }
  
  public static function board(array $path):array {
    if(count($path) < 4) {
      throw new InvalidRequestURIException();
    }
    $board = Model::get()->getBoard($path[3]);
    switch(count($path)) {
      case 4:
        return $board->jsonSerialize();
      case 5:
        switch(strtolower(alphanum($path[4]))) {
          case "activemedia":
            return array_reverse(Model::get()->getActiveMedia($board));
          default:
            throw new InvalidRequestURIException();
        }
        break;
      default:
        throw new InvalidRequestURIException();
    }
  }
  
  public static function boards(array $path):array {
    return Model::get()->getBoards();
  }
  
  public static function thread(array $path):array {
    if(count($path) < 5) {
      throw new InvalidRequestURIException();
    }
    $board = strtolower(alphanum($path[3]));
    $id = $path[4];
    $model = Model::get();
    $thread = $model->getThread($model->getBoard($board), $id);
    if(count($path) > 5) {
      switch($path[5]) {
        case "posts":
          $thread->loadAll();
          $data = ["thread"=>$thread->asArray(), "posts"=>[]];
          foreach($thread as $post) {
            /** @var Post $post */
            $data['posts'][] = $post->asArray();
          }
          return $data;
      }
    }
    return $thread->asArray();
  }
  
  public static function post(array $path):array {
    if(count($path) < 5) {
      throw new InvalidRequestURIException();
    }
    $board = strtolower(alphanum($path[3]));
    $id = $path[4];
    $model = Model::get();
    $post = $model->getPost($model->getBoard($board), $id);
    if (count($path) === 6 && $path[5] == 'html') {
      $content = PostRenderer::renderPost($post);
      return ["html"=>$content];
    }
    return $post->asArray();
  }

  public static function styles(array $path):array {
    return Config::getCfg("styles");
  }

}
class InvalidRequestURIException extends Exception {
  public function __construct() {
    parent::__construct("Invalid request URI");
  }
}
