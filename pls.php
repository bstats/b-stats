<?php
include("inc/config.php");

$db = Config::getConnectionRW();

$err = "";
if(isset($_POST['username'])){
    if($_POST['captcha'] == $_SESSION['captcha']){
        $username = $db->real_escape_string($_POST['username']);
        $password = md5($_POST['password']);
        $email = $db->real_escape_string($_POST['email']);
        $reason = $db->real_escape_string($_POST['why']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $db->query("INSERT INTO `request` (`ip`,`username`,`password`,`email`,`reason`,`time`) VALUES ('$ip','$username',UNHEX('$password'),'$email','$reason',UNIX_TIMESTAMP())");
        header("Location: https://archive.b-stats.org/pls.php");
        exit;
    }
    else{
        $err = "Invalid captcha.";
    }
}

$page = new Page(null,null,0);

$q=$db->query("SELECT * FROM `request` WHERE `ip`='{$_SERVER['REMOTE_ADDR']}'");
$r = $q->fetch_assoc();

if($q->num_rows === 0){
    $_SESSION['captcha']=rand(100000,999999);
    if($err != "") $page->appendToBody("<p class='center'>$err</p>");
    $page->appendToBody(Site::parseHtmlFragment("reqForm.html", ['__captcha__'], ['<img src="captcha.php">']));
}
else {
    if($r['accepted']==0)
        $page->appendToBody("<h2>Hold Your Horses</h2><p class='center'>You have successfully applied. Check this page or your email for your status.</p>");
    else
        $page->appendToBody("<h2>Congratulations</h2><p class='center'>Your application was reviewed and accepted.<br>You may now log in with the username and password that you chose.</p>");
}

echo $page->display();