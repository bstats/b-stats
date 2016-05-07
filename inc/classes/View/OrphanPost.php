<?php

namespace View;

use ImageBoard\Post;
use View\BoardPage;
use View\PostRenderer;

class OrphanPost extends BoardPage
{
  public function __construct(Post $p)
  {
    parent::__construct($p->board);
    $this->appendToBody(el('h2', 'Orphaned Post') . div('This post was found, but its thread is nowhere to be seen!', 'centertext'));
    $this->appendToBody(div('>>', 'sideArrows') . PostRenderer::renderPost($p));
  }
}