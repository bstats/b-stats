<?php
error_reporting(E_ALL);
include("inc/config.php");
$page = new FancyPage("FAQ","",0);
$page->setBody(file_get_contents("htmls/faq.html"));
echo $page->display();