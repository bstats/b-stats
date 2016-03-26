<?php

class FourOhFour extends FancyPage {
  function __construct($message = "") {
    parent::__construct("404 ;_;", "", 0);
    $this->setBody("<h1>404 ;_;</h1><div class='centertext' style='font-size:1.5em;'>{$message}</div><br><br>");
    $this->appendToBody("<p style='text-align:center;'><img src='/image/404.png' alt='file not found' /></p>");
    header("HTTP/1.0 404 Not Found");
  }
}