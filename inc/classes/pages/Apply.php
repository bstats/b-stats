<?php

class Apply extends FancyPage {

  public function __construct() {
    parent::__construct("Apply for Access", "", 0);
    $db = Config::getMysqliConnectionRW();

    $err = '';
    if (isset($_POST['username'])) {
      if (post('captcha') == $_SESSION['captcha']) {
        $username = $db->real_escape_string(post('username'));
        $password = md5(post('password'));
        $email = $db->real_escape_string(post('email'));
        $reason = $db->real_escape_string(post('why'));
        $ip = Site::ip();
        $db->query("INSERT INTO `request` (`ip`,`username`,`password`,`email`,`reason`,`time`) VALUES ('$ip','$username',UNHEX('$password'),'$email','$reason',UNIX_TIMESTAMP())");
        header('Location: /');
        exit;
      } else {
        $err = 'Invalid captcha.';
      }
    }
    $q = $db->query("SELECT * FROM `request` WHERE `ip`='".Site::ip()."'");

    if ($q->num_rows === 0) {
      $_SESSION['captcha'] = rand(100000, 999999);
      if ($err != '') {
        $this->appendToBody("<p class='center'>$err</p>");
      }
      $this->appendToBody(Site::parseHtmlFragment('reqForm.html', ['__captcha__'], ['<img src="/captcha" alt="captcha">']));
    }
    else {
      $r = $q->fetch_assoc();
      if ($r['accepted'] == 0) {
        $this->appendToBody("<h2>Hold Your Horses</h2><p class='center'>You have successfully applied. Check this page or your email for your status.</p>");
      } else if ($r['accepted'] == -1) {
        $this->appendToBody("<h2>Oh noes ;_;</h2><p class='center'>Sorry, your application has been reviewed and denied. Now that you have seen this message, you may submit a new application.</p>");
        $db->query("DELETE FROM `request` WHERE `ip`='".Site::ip()."'");
      } else if ($r['accepted'] == 1) {
        $this->appendToBody("<h2>Congratulations</h2><p class='center'>Your application was reviewed and accepted.<br>You may now log in with the username and password that you chose.</p>");
      }
    }
  }

}
