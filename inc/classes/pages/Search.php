<?php

class Search extends BoardPage {
  private $page;
  private $perPage;
  private $start;
  
  public function __construct(Board $board, array $path) {
    parent::__construct($board);
    $topLinks = div('', 'topLinks')
                    ->append('[' . a('Home', '/index') . ']')
                    ->append(' ['. a('Return', '/'.$this->board->getName().'/').']');
    if (!$board->isSwfBoard()) {
      $topLinks->append(' [' . a('Catalog', '/' . $this->board->getName() . '/catalog') . ']');
    }
    $this->appendToBody($topLinks);
    
    $this->appendToBody(el('h2','Board Search'));
    try {
      $method = $path[3] ?? "";
      if(method_exists(self::class, $method)) {
        $this->perPage = (int)get('perpage',250);
        $this->page = (int)get('page',0);
        $this->start = $this->perPage * $this->page;
        $result = $this->{$path[3]}($path[4] ?? NULL);
        $this->appendToBody(div($result->count . ' results.','centertext'));
        $pages = $this->makePageSelector($result->count);
        $this->appendToBody($pages);
        $i  = $this->start + 1;
        foreach($result->result as $post) {
          $this->appendToBody(div($i++." >>",'sideArrows').PostRenderer::renderPost($post));
        }
        $this->appendToBody($pages);
      } else {
        $this->appendToBody("<h3>Invalid search parameter '$method' provided</h3>");
      }
    } catch (Exception $e) {
      $this->appendToBody("<h3>Error: {$e->getMessage()}</h3>");
    }
    
    $this->appendToBody('<hr>'.$topLinks);
  }
  
  private function makePageSelector($count) {
    $pages = div('','centertext');
    $base = strtok($_SERVER['REQUEST_URI'],'?');
    $numPages = (int)($count / $this->perPage);
    if($this->page > 0) {
      $prev = $this->page - 1;
      $pages->append("<a href='$base?page=$prev'>&lt;&lt;</a> ");
    }
    for($i = 0; $i <= $numPages; $i++) {
      if($i == $this->page) {
        $pages->append(($i+1).' ');
      } else {
        $pages->append("<a href='$base?page=$i'>".($i+1).'</a> ');
      }
    }
    if($this->page < $numPages) {
      $next = $this->page + 1;
      $pages->append("<a href='$base?page=$next'>&gt;&gt;</a> ");
    }
    return $pages;
  }
  
  // fuuka support  
  private function image($md5) {
    return self::md5(bin2hex(base64_decode(str_replace(['-','_'],['+','/'],$md5).'==')));
  }
  
  private function md5($md5) {
    if($md5 == null || strlen($md5) !== 32) {
      throw new Exception('Invalid MD5');
    }
    $posts = Model::get()->getPostsByMD5($this->board, $md5);
    return $posts;
  }
  
  private function trip($trip) {
    if ($trip == "") {
      throw new Exception('Invalid trip');
    }
    $trip = str_replace("-","/",$trip);
    $posts = Model::get()->getPostsByTrip($this->board, $trip, $this->perPage, $this->start);
    return $posts;
  }
  
  private function id($id) {
    if($id == null) {
      throw new Exception('Invalid ID');
    }
    if($id == 'Heaven') {
      throw new Exception('ID Heaven posted so much that searching for his posts would slow the server down.');
    }
    $id = str_replace('-','/',$id);
    return Model::get()->getPostsByID($this->board, $id, $this->perPage, $this->start);
  }
}
