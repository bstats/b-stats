<?php
include("inc/config.php");
$page = new Page("b-stats archive","",0);
$html = "";
foreach(Model::getBoards() as $b){
    $html .= "<a href='/{$b['board_shortname']}/'>/{$b['board_shortname']}/ - {$b['board_longname']}</a><br>";
}
$page->setBody($html);
echo $page->display();