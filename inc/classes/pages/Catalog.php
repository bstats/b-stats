<?php

class Catalog extends BoardPage {
  public function __construct(\Board $board) {
    parent::__construct($board);
    if($board->isSwfBoard()) {
      throw new Exception("Catalogs don't work on upload boards");
    }
    $this->addToHead("<link rel='stylesheet' href='/css/bstats-catalog.css' type='text/css'>");
    $catalog = OldModel::getCatalog($board,false);
    $this->appendToBody(
              div('','topLinks')
              ->append('['.a('Home','/index').']')
              ->append(' ['.a('Return','/'.$board->getName().'/').']')
              .'<br/><br/>');
    $html = div('','extended-small')->set('id', 'threads');
    while($thread = $catalog->fetch_assoc()){
        $post = new Post($thread,$board);
        $html->append($post->display('catalog'));
    }
    $this->appendToBody($html);
  }
}
