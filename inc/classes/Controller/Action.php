<?php

namespace Controller;
use ImageBoard\Yotsuba;
use Model\FileInfo;
use Site\Config;
use Exception;
use Model\Model;
use Site\Site;

class Action
{
  public static function run(array $breadcrumbs)
  {
    if (method_exists('Controller\Action', $breadcrumbs[2])) {
      $method = $breadcrumbs[2];
      $target = self::$method();
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
    srand((int)(microtime(true)*1000));
    if(post('captcha') != $_SESSION['captcha']) {
      $_SESSION['captcha'] = rand(100000, 999999);
      throw new Exception("Invalid Captcha");
    }
    $_SESSION['captcha'] = rand(100000, 999999);
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
    }
    $file = self::checkUploadedFile();
    if($com == null && $file == null) {
      throw new Exception("Post must contain image");
    }
    $post = $model->addPost($board,
        post('resto',0),
        htmlspecialchars($name),
        $trip,
        htmlspecialchars(post('email')),
        htmlspecialchars(post('sub')),
        $com, $file);
    // auto-noko
    return "/{$board->getName()}/thread/{$post->getThreadId()}";
  }

  /**
   * @return FileInfo|null
   * @throws Exception
   */
  private static function checkUploadedFile()
  {
    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (
        !isset($_FILES['upfile']['error']) ||
        is_array($_FILES['upfile']['error'])
    ) {
      throw new Exception('Invalid parameters.');
    }

    // Check $_FILES['upfile']['error'] value.
    switch ($_FILES['upfile']['error']) {
      case UPLOAD_ERR_OK:
        break;
      case UPLOAD_ERR_NO_FILE:
        return null;
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new Exception('Exceeded filesize limit.');
      default:
        throw new Exception('Unknown errors.');
    }

    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
            $finfo->file($_FILES['upfile']['tmp_name']),
            array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ),
            true
        )) {
      throw new Exception('Invalid file format.');
    }

    if ($_FILES['upfile']['size'] > 4194304) {
      throw new Exception('Exceeded file size limit.');
    }

    $imageData = getimagesize($_FILES['upfile']['tmp_name']);
    $fileInfo = new FileInfo();
    $fileInfo->setSize($_FILES['upfile']['size'])
             ->setHash(md5_file($_FILES['upfile']['tmp_name'], true))
             ->setW($imageData[0])
             ->setH($imageData[1])
             ->setName(pathinfo($_FILES['upfile']['name'], PATHINFO_FILENAME))
             ->setExt('.'.pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION));
    return $fileInfo;
  }
}
