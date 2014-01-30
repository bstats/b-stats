<?php 
include("inc/config.php");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

switch($_GET['style']){
    case "yotsuba":
        Site::getUser()->setTheme("yotsuba");
        echo "true";
    break;
    case "tomorrow":
        Site::getUser()->setTheme("tomorrow");
        echo "true";
    break;
    case "yotsuba-pink":
        Site::getUser()->setTheme("yotsuba-pink");
        echo "true";
    break;
}
/*if(isset($_SERVER['HTTP_REFERER']))
    header("Location: {$_SERVER['HTTP_REFERER']}");
else
    header("Location: /");
 */