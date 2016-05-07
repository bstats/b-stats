<?php

namespace View\Pages;

use Site\Site;
use View\FancyPage;

class Dashboard extends FancyPage
{
  public function __construct()
  {
    parent::__construct("Dashboard", "", 1);

    if (isset($_GET['success'])) {
      $error = "<br>Successfully changed.";
    } else if (isset($_GET['failure'])) {
      $error = "<br>Password change unsuccessful.";
    } else {
      $error = "";
    }

    $user = Site::getUser();
    $this->appendToBody(Site::parseHtmlFragment("dashboard.html",
        ['<!-- username -->', '<!-- privilege -->', '<!-- theme -->', '<!-- error -->'],
        [$user->getUsername(), $user->getPrivilege(), $user->getTheme(), $error]));
  }
}
