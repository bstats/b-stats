<?php
/**
 * b-stats archive thread viewer.
 * @author terrance
 * @version 0.02
 * changelog:
 * 0.01
 * -Initial POS thread viewer.
 * 0.02
 * -Now using the Board class
 * 0.03
 * -Added thread stats
 */

include("inc/config.php");

/*
 * Establish variables.
 */
try{
    $board = new Board($_GET['b']);
}
catch(Exception $e){
    $page = new Page("b-stats ERROR LOL","<div class='errorMsg'>Error: {$e->getMessage()}</div><br>",0);
    echo $page->display();
    exit;
}

$page = new Page("/".$board->getName()."/ - ".$board->getLongName(),"",$board->getPrivilege());
$page->setBoard($board);


try{
    list($threadQ,$postQ) = $board->getThread($_GET['t']);
}
catch(Exception $e){
    $page->setTitle($page->getTitle()." - 404 ;_;");
    $page->setBody("<h1>404 ;_;</h1><div style='text-align:center;font-size:1.5em;'>Specified thread does not exist.</div><br><br>");
    header("HTTP/1.0 404 Not Found");
    echo $page->display();
    exit;
}

$threadDetails = $threadQ->fetch_assoc();
$i = 0;

$firstTime = 0;
$deleted = 0;
while($row = $postQ->fetch_assoc()){
    if($i++ == 0){
        $thread = new Thread($row['threadid'],$board,$threadDetails['sticky'],$threadDetails['closed']);
        $firstTime = $row['time'];
    }
    $thread->addPost(new Post($row,$board));
    if($row['deleted']==1)
        $deleted++;
    $lastTime = $row['time'];
}

$dur = secsToDHMS($lastTime - $firstTime);
$html = "<div class='board'>";
$html .= Site::parseHtmlFragment("threadStats.html",
    ["__threadid__","__posts__","__images__","__lifetime__","__deleted__"],
    [$threadDetails['threadid'],
     $threadDetails['replies'] - ($deleted - 1)." (".($threadDetails['replies']).")",
     $threadDetails['images'],
     "{$dur[0]}d {$dur[1]}h {$dur[2]}m {$dur[3]}s",
     $deleted]);
$html .= "<div class='thread'>";

$html .= $thread->displayThread();

$html .= "</div>";
$html .= "</div>";
$page->setBody($html);
echo $page->display();