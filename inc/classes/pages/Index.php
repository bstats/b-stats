<?php

class Index extends FancyPage {
  function __construct() {
    parent::__construct(Config::getCfg('site')['pagetitle'],"",0);
    $html = "<div class='boardlist_big'><h1>Archived Boards</h1><hr style='width:64px;'>";
    foreach(Model::get()->getBoards() as $b){
      $html .= Site::parseHtmlFragment("indexthread.html", 
            [ "%ago%",
              "%crawltime%",
              "%shortname%",
              "%longname%",
              "%posts%",
              "%threads%",
              "%firstcrawl%"
            ], 
            [ ago(time() - $b->getLastCrawl()),
              $b->getLastCrawl(),
              $b->getName(),
              $b->getLongName(),
              $b->getNoPosts(),
              $b->getNoThreads(),
              date("j F Y",$b->getFirstCrawl())]);
    }
    $html .= "</div>";
    $html .= "<script type='text/javascript' src='/script/boardUpdate.js'></script>";
    $this->setBody($html);
  }
}