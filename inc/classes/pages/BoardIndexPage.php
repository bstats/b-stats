<?php
/**
 * View for the paginated index of a board. Or not, in the case of an upload board.
 */
class BoardIndexPage extends BoardPage {
  public function __construct(\Board $board, int $page) {
    parent::__construct($board);
    if($board->isSwfBoard()) {
      $this->appendToBody(
              div('','topLinks navLinks')
              ->append('['.a('Home','/index').']')
              ->append(' ['.a('Catalog','/'.$board->getName().'/catalog').']'));
      $this->renderSwfBoard($page);
    } else {
      $this->appendToBody(
              div('','topLinks navLinks')
              ->append('['.a('Home','/index').']')
              ->append(' ['.a('Catalog','/'.$board->getName().'/catalog').']'));
      $this->renderPage($page);
    }
  }
  
  /**
   * Renders a page of a board's index.
   * @param int $page
   * @throws Exception if the page number is invalid.
   */
  private function renderPage(int $page) {
    if($page < 1) {
      throw new Exception("Invalid page number");
    }
    $main = div('','board');
    $threads = $this->board->getPage($page);
    $pages = $this->renderPageNumbers($page);
    $main->append($pages);
    foreach($threads as $thread){
      $thread->loadOP();
      if($this->board->getName() == "b"){
        $thread->loadLastN(3);
      } else {
        $thread->loadLastN(5);
      }
      $main->append($thread->displayThread());
      $main->append("\n<hr>\n");
    }
    $main->append($pages);
    $this->appendToBody($main);
  }
  
  private function renderPageNumbers(int $page, bool $catalog = true):string {
    $catalogLink = $catalog ? '<div class="pages cataloglink"><a href="./catalog">Catalog</a></div>' : '';
    if($page == 1){
      $linkList = Site::parseHtmlFragment("pagelist/pagelist_first.html",
              '<!-- catalog -->',$catalogLink);
    }
    elseif(1 < $page && $page < $this->board->getArchivePages() - 1){
      $linkList = Site::parseHtmlFragment("pagelist/pagelist_middle.html",
              '<!-- catalog -->',$catalogLink);
    }
    else{
      $linkList = Site::parseHtmlFragment("pagelist/pagelist_last.html",
              '<!-- catalog -->',$catalogLink);
    }
    $pages = "";
    for($p = 2; $p <= min($this->board->getArchivePages(), $this->board->getPages()); $p++){
      if ($p == $page) {
        $pages .= "[<strong><a href='$p'>$p</a></strong>] ";
      } else {
        $pages .= "[<a href='$p'>$p</a>] ";
      }
    }
    $start = max([$page - 7, $this->board->getPages() + 1]);
    $end = min([$page + 8, $this->board->getArchivePages() + 1]);
    if($end > $this->board->getPages()) {
      if ($start > $this->board->getPages() + 1) {
        $pages .= "[...] ";
      }
      for($i = $start; $i < $end; $i++) {
        if ($i == $page) {
          $pages .= "[<strong><a href='$i'>$i</a></strong>] ";
        } else {
          $pages .= "[<a href='$i'>$i</a>] ";
        }
      }
    }
    if($end < $this->board->getArchivePages()) {
      $pages .= "[...] ";
      $pages .= "[<a href='{$this->board->getArchivePages()}'>{$this->board->getArchivePages()}</a>] ";
    }
    return str_replace(["_prev_","_next_","_pages_"],[$page - 1, $page + 1, $pages],$linkList);
  }
  
  private function renderSwfBoard(int $page) {
    $threads = $this->board->getPage($page);
    $pages = $this->renderPageNumbers($page);
    $main = div('','board');
    $main->append($pages);
    $main->append("<table class='flashListing' style='border:none;'>".
                        "<tbody>".
                        "<tr>".
                        "<td class='postblock'>No.</td><td class='postblock'>Name</td>".
                        "<td class='postblock'>File</td><td class='postblock'></td>" .
                        "<td class='postblock'>Tag</td><td class='postblock'>Subject</td>".
                        "<td class='postblock'>Size</td><td class='postblock'>Date</td>".
                        "<td class='postblock'>Replies</td><td class='postblock'></td>".
                        "</tr>");
    $nums = array_map(function(Thread $thread){
      return $thread->getThreadId();
    }, $threads);
    sort($nums);
    $nums = array_slice($nums, 0, 5);
    foreach($threads as $thread){
      $thread->loadOP();
      $op = $thread->getPost(0);
      $preview = $op->getSubject() != "" ? $op->getSubject() : $op->com;
      $highlight = array_search($thread->getThreadId(), $nums) ? " class='highlightPost'" : "";
        $tr = "<tr$highlight>".
            "<td>{$op->getNo()}</td>".
            "<td class='name-col'><span class='name'>{$op->getName()}</span>".($op->getTripcode() != '' ? " <span class='postertrip'>{$op->getTripcode()}</span>" : "")."</td>".
            "<td>[<a href='".$op->getSwfUrl()."' title='".str_replace("'","&#39;",$op->getFilename())."' data-width='{$op->getWidth()}' data-height='{$op->getHeight()}' target='_blank'>".(mb_strlen($op->getFilename()) > 30 ? mb_substr($op->getFilename(), 0,25)."(...)" : $op->getFilename())."</a>]</td>".
            "<td>[<a href=''>Reposts</a>]</td>".
            "<td>[".str_replace("O","?",substr($thread->getTag(),0,1))."]</td>".
            "<td class='subject'><span title='".str_replace("'","&#39;",$preview)."'>".(mb_strlen($preview) > 30 ? mb_substr($preview, 0,30)."(...)" : $preview)."</span></td>".
            "<td>".human_filesize($op->getFilesize(),2)."</td>".
            "<td>".date("Y-m-d(D)H:i",$op->getTime())."</td>".
            "<td>{$thread->getChanPosts()}</td>".
            "<td>[<a href='thread/{$op->getNo()}'>View</a>]</td>".
            "</tr>";
        $main->append($tr);
    }
    $main->append("</tbody></table><br>");
    $main->append($pages);
    $this->appendToBody($main);
  }
}
