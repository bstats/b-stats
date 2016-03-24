<?php

class_exists('User');

class Site {

  const LEVEL_TERRANCE = 9;
  const LEVEL_ADMIN = 3;
  const LEVEL_SEARCH = 2;
  const LEVEL_USER = 1;
  const LEVEL_GUEST = 0;

  static $html_cache = [];

  static function isLoggedIn(): bool {
    return self::getUser()->getPrivilege() > 0;
  }

  static function canSearch(): bool {
    return self::getUser()->getPrivilege() >= Config::getCfg('permissions')['search'];
  }

  static function isAdmin(): bool {
    return self::getUser()->getPrivilege() >= Config::getCfg('permissions')['delete'];
  }

  static function isOwner(): bool {
    return self::getUser()->getPrivilege() === Config::getCfg('permissions')['owner'];
  }
  
  static function isBanned(): bool {
    return OldModel::banned($_SERVER['REMOTE_ADDR']);
  }
  static function ip():string {
    return $_SERVER['REMOTE_ADDR'];
  }
  static function backupInProgress():bool {
    return file_exists(self::getPath()."/inc/cfg/backup");
  }
  static function logIn($username, $password): bool {
    $user = OldModel::checkUsernamePasswordCombo($username, $password);
    if ($user instanceof User) {
      $_SESSION['user'] = $user;
      $uid = $user->getUID();
      $time = time();
      $ip = $_SERVER['REMOTE_ADDR'];
      Config::getMysqliConnectionRW()->query("INSERT INTO `logins` (`uid`,`time`,`ip`) VALUES ($uid,$time,'$ip')");
      return true;
    }
    return false;
  }

  static function logOut() {
    $_SESSION['user'] = null;
  }

  /**
   * 
   * @return User
   */
  static function getUser(): User {
    if (!isset($_SESSION['user'])) {
      $_SESSION['user'] = new User(0, "guest", 0, "yotsuba");
    }
    return $_SESSION['user'];
  }

  static function getPath() {
    return dirname(__FILE__, 3);
  }

  static function getImageHostname() {
    return Config::getCfg("servers")["images"]['hostname'];
  }

  static function getThumbHostname() {
    return Config::getCfg("servers")["thumbs"]['hostname'];
  }

  static function getSiteHostname() {
    return Config::getCfg("servers")["site"]["hostname"];
  }

  static function formatImageLink($md5bin, $ext) {
    
  }

  /**
   * Get the current protocol through which the user is viewing the site.
   * @return string
   */
  static function getSiteProtocol() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https:' : 'http:';
  }

  static function parseHtmlFragment($filename, $search = [], $replace = []) {
    if (!isset(self::$html_cache[$filename])) {
      $html = file_get_contents(self::getPath() . "/htmls/$filename");
      $html_cache[$filename] = $html;
    } else {
      $html = $html_cache[$filename];
    }
    return str_replace($search, $replace, $html);
  }

}
