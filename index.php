<?php
include("inc/config.php");
$page = new Page("b-stats archive","",0);
$html = "<div class='boardlist_big'><h1>Archived Boards</h1><hr style='width:64px;'>";
foreach(Model::getBoards() as $b){
    $html .= "<a href='/{$b['board_shortname']}/'>/{$b['board_shortname']}/ - {$b['board_longname']}</a><br>";
}
$html .= "</div>";
$page->setBody($html);
echo $page->display();