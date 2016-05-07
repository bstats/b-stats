<?php

namespace View\Pages;

use Model\Model;
use Site\Config;
use Site\Site;
use View\FancyPage;

class Index extends FancyPage
{
  function __construct()
  {
    parent::__construct(Config::getCfg('site')['pagetitle'], "", 0);

    $boards = Model::get()->getBoards();
    $archiveBoards = array_filter($boards, function ($b) {
      return $b->isArchive();
    });
    $plainBoards = array_filter($boards, function ($b) {
      return !$b->isArchive();
    });

    $html = "<div class='boardlist_big'><h1>Archived Boards</h1><hr style='width:64px;'>";
    foreach ($archiveBoards as $b) {
      $html .= Site::parseHtmlFragment("indexArchiveBoard.html",
          ["%ago%",
              "%crawltime%",
              "%shortname%",
              "%longname%",
              "%posts%",
              "%threads%",
              "%firstcrawl%"
          ],
          [ago(time() - $b->getLastCrawl()),
              $b->getLastCrawl(),
              $b->getName(),
              $b->getLongName(),
              $b->getNoPosts(),
              $b->getNoThreads(),
              date("j F Y", $b->getFirstCrawl())]);
    }
    $html .= "</div>";
    $html .= "<script type='text/javascript' src='/script/boardUpdate.js'></script>";

    if (count($plainBoards) > 0) {
      $html .= "<div class='boardlist_big'><h1>Boards</h1><hr style='width:64px;'>";
      foreach ($archiveBoards as $b) {
        $html .= Site::parseHtmlFragment("indexBoard.html",
            ["%ago%",
                "%crawltime%",
                "%shortname%",
                "%longname%",
                "%posts%",
                "%threads%",
                "%firstcrawl%"
            ],
            [ago(time() - $b->getLastCrawl()),
                $b->getLastCrawl(),
                $b->getName(),
                $b->getLongName(),
                $b->getNoPosts(),
                $b->getNoThreads(),
                date("j F Y", $b->getFirstCrawl())]);
      }
      $html .= "</div>";
    }
    $this->setBody($html);
  }
}