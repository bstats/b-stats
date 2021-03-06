<?php

namespace Site;
use Site\Config;
use Model\OldModel;
use Site\Site;

class User
{
  public static $guest;

  private $uid;
  private $username;
  private $privilege;
  private $theme;

  function __construct($uid, $username, $privilege, $theme)
  {
    $this->uid = (int)$uid;
    $this->username = $username;
    $this->privilege = (int)$privilege;
    $this->theme = $theme;
  }

  function getUID()
  {
    return $this->uid;
  }

  function getUsername()
  {
    return $this->username;
  }

  function getPrivilege()
  {
    return $this->privilege;
  }

  function getTheme()
  {
    return $this->theme;
  }

  function setTheme($theme)
  {
    if (in_array($theme, array_keys(Config::getCfg('styles')))) {
      if ($this->uid != 0) {
        OldModel::updateUserTheme($this->uid, $theme);
      }
      $this->theme = $theme;
    }
  }

  function canSearch()
  {
    return $this->privilege >= Site::LEVEL_SEARCH;
  }
}

User::$guest = new User(0, "guest", 0, "yotsuba");
