<?php
include("inc/config.php");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if(in_array($_POST['b'], ['b','f','hm','lgbt'])){
    $dbl = Config::getConnectionRW();
    $post = $dbl->real_escape_string($_POST['p']);
    $thread = $dbl->real_escape_string($_POST['t']);
    $time = time();
    $uid = Site::getUser()->getUID();
    $ip = $_SERVER['REMOTE_ADDR'];
    $dbl->query("INSERT INTO `reports` (`uid`,`board`,`time`,`ip`,`no`,`threadid`) VALUES ('$uid','{$_POST['b']}',$time,'$ip',$post,$thread)");
    echo json_encode(true);
}
else echo json_encode(false);