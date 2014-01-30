<?php
include("inc/config.php");
$p = new Page("404 File Not Found ;_;","<h3>404 File Not Found ;_;</h3><p style='text-align:center;'><img src='/image/404.png' alt='file not found'  /></p>",0);
echo $p->display();
