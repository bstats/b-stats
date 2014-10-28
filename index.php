<?php
include("inc/config.php");
$page = new Page("b-stats archive","",0);
$html = "<div class='boardlist_big'><h1>Archived Boards</h1><hr style='width:64px;'>";
foreach(Model::getBoards() as $b){
    $html .= "<a href='/{$b['shortname']}/'>/{$b['shortname']}/ - {$b['longname']}</a><br>";
    $html .= "<span style='font-size:0.8em; position:relative; top: -1em;opacity:0.5;'>Last update: <span class='ago' data-board='{$b['shortname']}' data-utc='{$b['last_crawl']}'>".ago(time() - $b['last_crawl'])."</span></span><br>";
}
$html .= "</div>";
$html .= "<script type='text/javascript' src='/script/boardUpdate.js'></script>";
$page->setBody($html);
echo $page->display();