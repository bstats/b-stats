<?php

class Action {
  public static function run(array $breadcrumbs) {
    if(method_exists('Action', $breadcrumbs[2])){
      $target = self::{$breadcrumbs[2]}();
    } else {
      throw new Exception("Unknown action");
    }
    if ($target !== '') {
      header("Location: $target");
    }
    exit;
  }
  
  static function login():string {
    Site::logIn(post('username'), post('password'));
    return $_SERVER['HTTP_REFERER'] ?? "/";
  }
  
  static function logout():string {
    Site::logOut();
    return "/";
  }
  
  static function changePassword():string {
    if(Model::get()->changePassword(Site::getUser()->getUID(), post('old'), post('new'))){
      return '/dash?success';
    }
    return '/dash?failure';
  }
  
  static function setStyle():string {
    $styles = Config::getCfg("styles");
    if(in_array(post('style'), array_keys($styles))) {
      Site::getUser()->setTheme(post('style'));
    }
    return '';
  }
  
  static function reportPost():string {
    try {
      $board = Model::get()->getBoard(alphanum(post('b')));
      Model::get()->addReport($board, post('p'), post('t'));
    } catch (Exception $ex) {
      echo json_encode($ex->getMessage());
    }
    return '';
  }
}
