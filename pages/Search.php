<?php

class Search extends FancyPage {
  public function __construct() {
    parent::__construct("Search","",site::LEVEL_SEARCH);
    
    $this->appendToBody("Advanced search will be here.");
  }
}
