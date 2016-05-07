<?php

namespace View\Pages;

use Model\OldModel;
use Site\Site;
use View\Page;

class Banned extends Page
{
  public function __construct()
  {
    parent::__construct("Banned", "You're banned.");
    $banInfo = OldModel::getBanInfo($_SERVER['REMOTE_ADDR']);
    $expires = $banInfo['expires'] == 0 ? "Never" : date("Y-m-d h:i:s T", $banInfo['expires']);
    $this->body = Site::parseHtmlFragment('banned.html', ['__ip__', '__reason__', '__expires__'], [$_SERVER['REMOTE_ADDR'], $banInfo['reason'], $expires]);
    $this->title = "/b/ stats: ACCESS DENIED";
  }
}
