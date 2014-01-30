<?php

include 'inc/config.php';

try{
    $board = new Board($_GET['b']);
    if($board->isSwfBoard())
        throw new Exception("Board is a swf board. Use the normal <a href='/{$_GET['b']}/'>board index</a>.");
}
catch(Exception $e){
    $page = new Page("b-stats ERROR LOL","<div class='errorMsg'>Error: {$e->getMessage()}</div><br>",0);
    echo $page->display();
    exit;
}

$page = new Page("/".$board->getName()."/ - ".$board->getLongName(),"",$board->getPrivilege());
$page->setBoard($board);
$page->addToHead("<link rel='stylesheet' href='/css/bstats-catalog.css' type='text/css'>");
$catalog = Model::getCatalog($board);
$html="<div id='threads' class='extended-small'>";
while($thread = $catalog->fetch_assoc()){
    $post = new Post($thread,$board);
    $html .= $post->display('catalog');
}
$html.="</div>";
$page->setBody($html);
echo $page->display();