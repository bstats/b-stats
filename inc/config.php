<?php
define("CONFIG_INC_TIME",microtime(true));
include "SiteFunctions.php";
include "SiteClasses.php";
include "ChanClasses.php";

if(php_sapi_name() != "cli"){
    session_start();
    if(!isset($_SESSION['style'])){
        $_SESSION['style']="yotsuba";
        $_SESSION['user'] = null;
        $_SESSION['privilege'] = 0;
        $_SESSION['banned'] = false;
    }
}


