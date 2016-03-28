<?php
/**
 * Class for board-centric pages, such as:
 *  - index pages
 *  - catalog
 *  - thread view
 */
class BoardPage extends FancyPage {
  /** 
   * @var Board
   */
  protected $board;
  
  public function __construct(Board $board) {
    parent::__construct("/{$board->getName()}/ - {$board->getLongName()}", "", $board->getPrivilege());
    $boardBanner = div("","boardBanner centertext")
            ->append(div("/{$board->getName()}/ - {$board->getLongName()}", "boardTitle"))
            ->append(a("View this board on 4chan", '//boards.4chan.org/'.$board->getName()));
    $this->appendToBody("<hr>".$boardBanner."<hr>");
    
    $this->board = $board;
  }
}
