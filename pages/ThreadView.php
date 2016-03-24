<?php
/**
 * View for a 4chan thread.
 */
class ThreadView extends BoardPage {
  function __construct(Thread $t) {
    parent::__construct($t->getBoard());
    $t->loadAll();
    $this->appendToBody($t->displayThread());
  }
}