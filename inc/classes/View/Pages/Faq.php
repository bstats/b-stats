<?php

namespace View\Pages;

use Site\Site;
use View\FancyPage;

class Faq extends FancyPage
{
  function __construct()
  {
    parent::__construct("FAQ", "", 0);
    $this->setBody(Site::parseHtmlFragment("faq.html"));
  }
}