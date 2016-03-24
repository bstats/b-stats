<?php

class Router {
  /**
   * Routes to the proper view given a path relative to the site root.
   * @param string $path
   * @throws Exception 
   * @throws NotFoundException if the path can't be routed
   */
  public static function route(string $path) {
    $exploded = explode('/', $path);
    $base = strtolower($exploded[1]);
    switch ($base) {
      case "do":
        // Action pages begin with do
        Action::run($exploded);
        break;
      case "api":
        // API endpoints begin with api
        $page = PublicApi::run($exploded);
        break;
      case "adminapi":
        
        break;
      case "_":
        // Fuuka API support
        $page = FuukaApiAdaptor::run($exploded);
        break;
      default:
        $boards = Model::get()->getBoards();
        if (array_key_exists($base, $boards)) {
          $board = $boards[$base];
          if (isset($exploded[2])) {
            switch ($exploded[2]) {
              case "catalog":
                $page = new Catalog($board);
                break;
              case "thread":
              case "res":
                $num = $exploded[3] ?? "";
                if (is_numeric($num)) {
                  $page = new ThreadView(Model::get()->getThread($board, $num));
                } else {
                  throw new Exception("Invalid thread id provided");
                }
                break;
              case "":
                $page = new BoardIndexPage($boards[$base], 1);
                break;
              default:
                if (is_numeric($exploded[2])) {
                  $page = new BoardIndexPage($boards[$base], $exploded[2]);
                } else {
                  throw new Exception("Unknown board page requested");
                }
                break;
            }
          } else {
            header("Location: $path/");
            exit;
          }
        } else {
          $pages = json_decode(file_get_contents("inc/cfg/pages.json"), true);
          if (array_key_exists($base, $pages)) {
            $page = new $pages[$base]();
          }
        }
        break;
    }
    if (isset($page)) {
      echo $page->display();
    } else {
      throw new NotFoundException('Unrecognized URL: ' . $path);
    }
  }
}
