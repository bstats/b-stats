<?php
include("inc/config.php");
$page = new FancyPage("b-stats archive","",0);
$html = "<div class='boardlist_big'><h1>Archived Boards</h1><hr style='width:64px;'>";
foreach(Model::getBoards() as $b){
    $html .= Site::parseHtmlFragment("indexthread.html", 
          [ "%ago%",
            "%crawltime%",
            "%shortname%",
            "%longname%",
            "%posts%",
            "%threads%",
            "%firstcrawl%"
          ], 
          [ ago(time() - $b['last_crawl']),
            $b['last_crawl'],
            $b['shortname'],
            $b['longname'],
            $b->getNoPosts(),
            $b->getNoThreads(),
            date("j F Y",$b->getFirstCrawl())]);
}
$html .= "</div>";
$html .= "<script type='text/javascript' src='/script/boardUpdate.js'></script>";
$page->setBody($html);
echo $page->display();