<?php

/**
 * View for a 4chan thread.
 */
class ThreadView extends BoardPage {

  function __construct(Thread $thread) {
    parent::__construct($thread->getBoard());
    $topLinks = div('', 'topLinks navLinks')
                    ->append('[' . a('Home', '/index') . ']')
                    ->append(' ['. a('Return', '/'.$this->board->getName().'/').']');
    if (!$thread->getBoard()->isSwfBoard()) {
      $topLinks->append(' [' . a('Catalog', '/' . $this->board->getName() . '/catalog') . ']');
    }
    $this->appendToBody($topLinks . '<br/><br/>');
    $thread->loadAll();
    $dur = secsToDHMS($thread->getPost($thread->getPosts()-1)->getTime() - $thread->getPost(0)->getTime());
    $board = div(Site::parseHtmlFragment("threadStats.html", ["__threadid__", "__posts__", "__posts_actual__", "__images__", "__images_actual__", "__lifetime__", "__deleted__", "<!--4chanLink-->", "<!--tag-->"], 
            [$thread->getThreadId(),
              $thread->getChanPosts(),
              ($thread->getPosts() - 1),
              $thread->getChanImages(),
              ($thread->getImages() - 1),
              "{$dur[0]}d {$dur[1]}h {$dur[2]}m {$dur[3]}s",
              $thread->getDeleted(),
              $thread->isActive() ? "<a target='_blank' href='//boards.4chan.org/{$this->board->getName()}/thread/{$thread->getThreadId()}'>View on 4chan</a>" : "Thread is dead.",
              $thread->getTag() != null ? "<br>Tagged as: " . $thread->getTag() : ""]), 'board');
    $this->appendToBody($board->append(div($thread->displayThread(), 'thread')));
    $bottomLinks = $topLinks->set('class','');
    $this->appendToBody("<hr>".$bottomLinks);
  }

}
