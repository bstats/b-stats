<?php

class Search extends BoardPage {
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
      if(method_exists(self::class, $path[3])) {
        $this->{$path[3]}($path[4] ?? NULL);
      } else {
        $this->appendToBody("<h3>No such search parameter found</h3>");
      }
    } catch (Exception $e) {
      $this->appendToBody("<h3>Error: {$e->getMessage()}</h3>");
    }
    
    $this->appendToBody('<hr>'.$topLinks);
  }
  
  private function md5($md5) {
    if($md5 == null || strlen($md5) !== 32) {
      throw new Exception('Invalid MD5');
    }
    $posts = Model::get()->getPostsByMD5($this->board, $md5);
    
    $this->appendToBody(div('Found '.count($posts).' reposts (limit is 500 for now)','centertext').'<br>');
    $i  = 1;
    foreach($posts as $post) {
      $this->appendToBody(div($i++." >>",'sideArrows').PostRenderer::renderPost($post));
    }
  }
  
  private function id($id) {
    if($id == null) {
      throw new Exception('Invalid ID');
    }
    $posts = Model::get()->getPostsByID($this->board, str_replace('-','/',$id));
    
    $this->appendToBody(div('Found '.count($posts).' posts with ID '.$id.' (limit is 500 for now)','centertext').'<br>');
    $i  = 1;
    foreach($posts as $post) {
      $this->appendToBody(div($i++." >>",'sideArrows').PostRenderer::renderPost($post));
    }
  }
}
