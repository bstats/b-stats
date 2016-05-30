<?php

namespace Site;

use Site\Config;
use Model\Model;
use NotFoundException;
use Site\PermissionException;

class_exists('Site\User');

class Site
{

  const LEVEL_TERRANCE = 9;
  const LEVEL_ADMIN = 3;
  const LEVEL_SEARCH = 2;
  const LEVEL_USER = 1;
  const LEVEL_GUEST = 0;

  static $html_cache = [];

  /**
   * @throws PermissionException
   */
  static function requirePrivilege(int $privilege)
  {
    if (self::getUser()->getPrivilege() < $privilege) {
      throw new PermissionException(self::getUser()->getPrivilege(), $privilege);
    }
  }

  static function isLoggedIn(): bool
  {
    return self::getUser()->getPrivilege() > 0;
  }

  static function canSearch(): bool
  {
    return self::getUser()->getPrivilege() >= Config::getCfg('permissions')['search'];
  }

  static function isAdmin(): bool
  {
    return self::getUser()->getPrivilege() >= Config::getCfg('permissions')['delete'];
  }

  static function isOwner(): bool
  {
    return self::getUser()->getPrivilege() === Config::getCfg('permissions')['owner'];
  }

  static function isBanned(): bool
  {
    return Model::get()->isBanned($_SERVER['REMOTE_ADDR']);
  }

  static function ip(): string
  {
    return $_SERVER['REMOTE_ADDR'];
  }

  static function enterBackupMode()
  {
    touch(self::getPath() . "/cfg/backup");
  }

  static function exitBackupMode()
  {
    unlink(self::getPath() . "/cfg/backup");
  }

  static function backupInProgress(): bool
  {
    return file_exists(self::getPath() . "/cfg/backup");
  }

  /**
   * Logs in the session user. Throws exception if username
   * and password don't match any users.
   *
   * @param string $username
   * @param string $password
   * @throws NotFoundException
   */
  static function logIn(string $username, string $password)
  {
    $user = Model::get()->getUser($username, $password);
    $_SESSION['user'] = $user;
    $uid = $user->getUID();
    $time = time();
    $ip = $_SERVER['REMOTE_ADDR'];
    Config::getPDOConnectionRW()->query("INSERT INTO `logins` (`uid`,`time`,`ip`) VALUES ($uid,$time,'$ip')");
  }

  static function logOut()
  {
    $_SESSION['user'] = User::$guest;
  }

  /**
   *
   * @return User
   */
  static function getUser(): User
  {
    if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
      $_SESSION['user'] = User::$guest;
    }
    return $_SESSION['user'];
  }

  static function getPath():string
  {
    return dirname(__FILE__, 4);
  }

  static function getImageHostname():string
  {
    return Config::getCfg("servers")["images"]['hostname'];
  }

  static function getThumbHostname():string
  {
    return Config::getCfg("servers")["thumbs"]['hostname'];
  }

  static function getSiteHostname()
  {
    return Config::getCfg("servers")["site"]["hostname"];
  }

  static function formatImageLink($md5bin, $ext)
  {

  }

  /**
   * Get the current protocol through which the user is viewing the site.
   * @return string
   */
  static function getSiteProtocol()
  {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https:' : 'http:';
  }

  static function parseHtmlFragment($filename, $search = [], $replace = [])
  {
    if (!isset(self::$html_cache[$filename])) {
      $html = file_get_contents(self::getPath() . "/htmls/$filename");
      $html_cache[$filename] = $html;
    } else {
      $html = $html_cache[$filename];
    }
    return str_replace($search, $replace, $html);
  }

}
