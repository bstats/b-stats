<?php

class PostSearchResult {
  /**
   * How many posts are actually in the DB
   * @var int
   */
  public $count;
  
  /**
   * Post array
   * @var \Post[]
   */
  public $result;
  
  public function __construct($count, $result) {
    $this->count = $count;
    $this->result = $result;
  }
}
