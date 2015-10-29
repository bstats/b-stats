<?php
class Config {   
  /** @var mysqli reference to mysqli object */
  static $mysqli;

  /** @var mysqli reference to read and write privileged mysql connection */
  static $mysqli_rw;

  /** @var array json configuration file, array */
  static $cfg;

  /** @var array json configuration file for mysql */
  static $sql_cfg;

  /** 
   * Gets an instance of mysqli with read-only permissions.
   * @return mysqli 
   */
  static function getConnection(){
    if(self::$mysqli == null){
      self::$mysqli = new mysqli(
              self::getSqlCfg('read-only')['server'],
              self::getSqlCfg('read-only')['username'],
              self::getSqlCfg('read-only')['password'],
              self::getSqlCfg('read-only')['db']);
      self::$mysqli->set_charset("utf8");
    }
    return self::$mysqli;
  }

  /**
   * Close the read-only connection.
   */
  static function closeConnection(){
    self::$mysqli->close();
    self::$mysqli = null;
  }

  /** 
   * Gets an instance of mysqli with read+write permissions.
   * @return mysqli 
   */
  static function getConnectionRW(){
    if(self::$mysqli_rw == null){
      self::$mysqli_rw = new mysqli(
              self::getSqlCfg('read-write')['server'],
              self::getSqlCfg('read-write')['username'],
              self::getSqlCfg('read-write')['password'],
              self::getSqlCfg('read-write')['db']);
      self::$mysqli_rw->set_charset("utf8");
    }
    return self::$mysqli_rw;
  }

  /**
   * Close the read+write connection.
   */
  static function closeConnectionRW(){
    self::$mysqli_rw->close();
    self::$mysqli_rw = null;
  }

  /**
   * Loads a value from the main configuration file.
   * The file is cached upon the first call.
   * Returns null if key doesn't exist.
   * @param string $key
   * @return mixed|null
   */
  static function getCfg($key){
    if(self::$cfg == null){
      self::$cfg = json_decode(file_get_contents(
              dirname(__FILE__)."/../cfg.json"),true);
    }
    return isset(self::$cfg[$key]) ? self::$cfg[$key] : null;
  }

  /**
   * Sets a value in the main configuration file
   * @param string $key
   * @param mixed $value
   */
  static function setCfg($key,$value){
    //make sure we have the latest version of the config, for good measure.
    self::$cfg = json_decode(file_get_contents(
            dirname(__FILE__)."/../cfg.json"),true);
    self::$cfg[$key] = $value;
    file_put_contents(dirname(__FILE__)."../cfg.json",
            json_encode(self::$cfg, JSON_PRETTY_PRINT));
  }

  /**
   * Load a value from the SQL configuration file.
   * @param string $key
   * @return mixed
   */
  static function getSqlCfg($key){
    return self::getCfg("mysql")[$key];
  }
}