<?php

require_once("inc/config.php");

$page = new Page("Hold yer horses","<h2>Backup in Progress. Check back later.</h2>");

echo $page->display();