<?php

require_once("inc/config.php");

$page = new Page("Hold yer horses",null,0);

$page->appendToBody("<h2>Backup in Progress. Check back later.</h2>");

echo $page->display();