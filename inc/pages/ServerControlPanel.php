<?php

class ServerControlPanel extends FancyPage {
  function __construct() {
    parent::__construct("Server Control Panel", "", Site::LEVEL_TERRANCE);
    $this->appendToBody(Site::parseHtmlFragment("scp.html",
            ['<!-- uptime -->'],[`uptime`.'<br>'.human_filesize(disk_free_space(__DIR__),2)." free"]));
    
  }
}
