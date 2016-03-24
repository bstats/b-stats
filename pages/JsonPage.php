<?php

class JsonPage implements IPage {
  private $data;
  private $pp;
  
  /**
   * Constructs a JSON page. For API results and such.
   * @param any $jsonData The data to be encoded in the JSON string.
   * @param bool $pretty_print should the data be pretty-printed?
   */
  public function __construct($jsonData, bool $pretty_print = false) {
    $this->data = $jsonData;
    $this->pp = $pretty_print;
  }
  //put your code here
  public function display():string {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");
    return json_encode($this->data);
  }
}
