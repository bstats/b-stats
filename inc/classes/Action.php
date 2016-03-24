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
    if(!Site::logIn(post('username'), post('password'))) {
      throw new Exception("Invalid username or password");
    }
    return $_SERVER['HTTP_REFERER'] ?? "/";
  }
  
  static function logout():string {
    Site::logOut();
    return "/";
  }
  
  static function changePassword():string {
    if(OldModel::changePassword(Site::getUser()->getUID(), post('old'), post('new'))){
      return '/dash?success';
    }
    return '/dash?failure';
  }
  
  static function setStyle():string {
    $styles = Config::getCfg("styles");
    if(in_array(post('style'), $styles)) {
      Site::getUser()->setTheme(post('style'));
    }
    return '';
  }
}
