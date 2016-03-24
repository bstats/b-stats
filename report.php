<?php
include("inc/config.php");
include("inc/globals.php");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

try {
  $board = Model::get()->getBoard(alphanum(post('b')));
  Model::get()->addReport($board, post('p'), post('t'));
} catch (Exception $ex) {
  echo json_encode($ex->getMessage());
}