<?php

namespace Controller;
use ImageBoard\Yotsuba;
use Site\Config;
use Exception;
use Model\Model;
use Site\Site;

class Action
{
  public static function run(array $breadcrumbs)
  {
    if (method_exists('Controller\Action', $breadcrumbs[2])) {
      $target = self::{$breadcrumbs[2]}();
    } else {
      throw new Exception("Unknown action");
    }
    if ($target !== '') {
      header("Location: $target");
    }
    exit;
  }

  static function login():string
  {
    Site::logIn(post('username'), post('password'));
    return $_SERVER['HTTP_REFERER'] ?? "/";
  }

  static function logout():string
  {
    Site::logOut();
    return "/";
  }

  static function changePassword():string
  {
    if (Model::get()->changePassword(Site::getUser()->getUID(), post('old'), post('new'))) {
      return '/dash?success';
    }
    return '/dash?failure';
  }

  static function setStyle():string
  {
    $styles = Config::getCfg("styles");
    if (in_array(post('style'), array_keys($styles))) {
      Site::getUser()->setTheme(post('style'));
    }
    return '';
  }

  static function reportPost():string
  {
    try {
      $board = Model::get()->getBoard(alphanum(post('b')));
      Model::get()->addReport($board, post('p'), post('t'));
    } catch (Exception $ex) {
      echo json_encode($ex->getMessage());
    }
    return '';
  }

  /**
   * Naiive attempt at making an imageboard.
   *
   * @todo: Make this not as spaghetti
   *
   * @return string
   * @throws Exception
   * @throws \NotFoundException
   */
  static function post():string
  {
    $model = Model::get();
    if(post('mode') != 'regist') {
      throw new Exception("invalid mode");
    }
    $board = $model->getBoard(post('board'));
    if($board->isArchive()) {
      throw new Exception("Board is an archive");
    }
    $name = post('name', 'Anonymous');
    if($name == ''){
      $name = 'Anonymous';
    }
    $trip = Yotsuba::parseTripcode($name);
    if($trip == false){
      $trip = null;
    } else {
      $trip = '!'.$trip;
    }
    $name = strtok($name, '#');
    $com = post('com');
    if($com == '') {
      $com = null;
    } else {
      $com = Yotsuba::toHtml($com, []);
    }
    $post = $model->addPost($board,
        post('resto',0),
        $name,
        $trip,
        htmlspecialchars(post('email')),
        htmlspecialchars(post('sub')),
        $com);
    // auto-noko
    return "/{$board->getName()}/thread/{$post->getThreadId()}";
  }
}
