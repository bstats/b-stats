<?php
include("inc/config.php");

switch($_REQUEST['action']){
    case "logout":
        Site::setPrivilege(0);
        $_SESSION['user'] = null;
        break;
    case "login":
        $user = Model::checkUsernamePasswordCombo($_REQUEST['username'], $_REQUEST['password']);
        if($user instanceof User){
            $_SESSION['user'] = $user;
            $_SESSION['style'] = $user->getTheme();
            Site::setPrivilege($user->getPrivilege());
            $uid = $user->getUID();
            $time = time();
            $ip = $_SERVER['REMOTE_ADDR'];
            Config::getConnectionRW()->query("INSERT INTO `logins` (`uid`,`time`,`ip`) VALUES ($uid,$time,'$ip')");
        }
        break;
}
if(isset($_SERVER['HTTP_REFERER']))
    header("Location: ".$_SERVER['HTTP_REFERER']);
else
    header("Location: /");