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
 * 0.04
 * -More OOP stuffs.
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
    $thread = Thread::fromDB($board,$_GET['t']);
    $thread->loadAll();
}
catch(Exception $e){
    $page->setTitle($page->getTitle()." - 404 ;_;");
    $page->setBody("<h1>404 ;_;</h1><div style='text-align:center;font-size:1.5em;'>{$e->getMessage()}</div><br><br>");
    header("HTTP/1.0 404 Not Found");
    echo $page->display();
    exit;
}
$dur = secsToDHMS($thread->getPost($thread->getPosts()-1)->getTime() - $thread->getPost(0)->getTime());
$html = "<div class='board'>";
$html .= Site::parseHtmlFragment("threadStats.html",
    ["__threadid__","__posts__","__posts_actual__","__images__","__images_actual__","__lifetime__","__deleted__","<!--4chanLink-->","<!--tag-->"],
    [$_GET['t'],
     $thread->getChanPosts(),
    ($thread->getPosts()-1),
     $thread->getChanImages(),
    ($thread->getImages() - 1),
     "{$dur[0]}d {$dur[1]}h {$dur[2]}m {$dur[3]}s",
     $thread->getDeleted(),
     $thread->isActive() ? "<a target='_blank' href='//boards.4chan.org/$board/thread/{$_GET['t']}'>View on 4chan</a>" : "Thread is dead.",
     $thread->getTag()!=null ? "<br>Tagged as: ".$thread->getTag() : ""]);
$html .= "<div class='thread'>";

$html .= $thread->displayThread();

$html .= "</div>";
$html .= "</div>";
$page->setBody($html);
echo $page->display();