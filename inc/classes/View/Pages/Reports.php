<?php

namespace View\Pages;

use Model\Model;
use Model\OldModel;
use Site\Config;
use Site\Site;
use View\FancyPage;

class Reports extends FancyPage
{
  public function __construct()
  {
    parent::__construct("Report Queue", el('h2', "Reports"), Config::getCfg('permissions')['delete']);
    $reports = Model::get()->getReports();
    $html = "<table class='reportTable'><tr><th colspan='3'>Report Queue</th></tr><tr><th style='width:3em;'>Times</th><th>Post</th><th style='width:20em;'>Options</th></tr>";
    foreach ($reports as $report) {
      $hash = bin2hex($report['md5']);
      $html .= "<tr id='report{$report['no']}'>";
      $html .= "<td>" . $report['count'] . "</td>";
      $html .= "<td><a href='{$report['threadid']}#p{$report['no']}' data-board='{$report['board']}' data-thread='{$report['threadid']}' data-post='{$report['no']}' class='quotelink noEmbed'>&gt;&gt;{$report['no']}</a></td>";
      $html .= "<td><a class='button' href='javascript:deletePost({$report['no']},\"{$report['board']}\");' >Delete&nbsp;Post</a>&nbsp;";
      $html .= "<a class='button' href='javascript:banImage(\"$hash\");' id='ban$hash'>Ban&nbsp;Image</a>&nbsp;";
      $html .= "<a class='button' href='javascript:deleteReport({$report['no']},\"{$report['board']}\");'>Delete&nbsp;Report</a>&nbsp;";
      $html .= "<a class='button' href='javascript:banReporter({$report['no']},\"{$report['board']}\");'>Ban&nbsp;Reporter</a></td>";
      $html .= "</tr>";
    }
    $html .= "</table>";

    if (Site::getUser()->getPrivilege() >= Config::getCfg('permissions')['owner']) {
      $html .= "<br><table class='reportTable'><tr><th colspan='3'>Last Few Deleted Posts</th></tr><tr><th style='width:3em;'>Board</th><th>Post</th><th style='width:7em;'>Options</th></tr>";
      foreach (Model::get()->getBoards() as $board) {
        $lastFew = OldModel::getLastNDeletedPosts($board->getName(), 5);
        foreach ($lastFew as $report) {
          $html .= "<tr id='report{$report['no']}'>";
          $html .= "<td>" . $board->getName() . "</td>";
          $html .= "<td>&gt;&gt;{$report['no']} ({$report['name']}{$report['trip']})</td>";
          $html .= "<td><a class='button' href='javascript:restorePost({$report['no']},\"{$board->getName()}\");' >Restore&nbsp;Post</a></td>";
          $html .= "</tr>";
        }
      }
      $html .= "</table>";
    }
    $this->appendToBody($html);
  }
}
