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

  /** @var array cache of json cfg files */
  static $json_cache;
  
  /** @var PDO pdo */
  static $pdo;
  static $pdo_rw;
  
  /**
   * @return PDO PDO object.
   */
  static function getPDOConnection():PDO {
    if(self::$pdo == null) {
      $cfg = self::getCfg('mysql')['read-only'];
      self::$pdo = new PDO("mysql:host={$cfg['server']};dbname={$cfg['db']};charset=utf8mb4", $cfg['username'], $cfg['password']);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    return self::$pdo;
  }
  
  /**
   * @return \PDO
   */
  static function getPDOConnectionRW():PDO {
    if(self::$pdo_rw == null) {
      $cfg = self::getCfg('mysql')['read-write'];
      self::$pdo_rw = new PDO("mysql:host={$cfg['server']};dbname={$cfg['db']};charset=utf8mb4", $cfg['username'], $cfg['password']);
      self::$pdo_rw->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$pdo_rw->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    return self::$pdo_rw;
  }
  
  static function closePDOConnectionRW() {
    self::$pdo_rw = null;
  }
  
  /** 
   * Gets an instance of mysqli with read-only permissions.
   * @return mysqli 
   */
  static function getMysqliConnection(){
    if(self::$mysqli == null){
      $driver = new mysqli_driver();
      $driver->report_mode = MYSQLI_REPORT_STRICT;
      self::$mysqli = new mysqli(
              self::getCfg('mysql')['read-only']['server'],
              self::getCfg('mysql')['read-only']['username'],
              self::getCfg('mysql')['read-only']['password'],
              self::getCfg('mysql')['read-only']['db']);
      self::$mysqli->set_charset("utf8");
    }
    return self::$mysqli;
  }

  /** 
   * Gets an instance of mysqli with read+write permissions.
   * @return mysqli 
   */
  static function getMysqliConnectionRW(){
    if(self::$mysqli_rw == null){
      $driver = new mysqli_driver();
      $driver->report_mode = MYSQLI_REPORT_STRICT;
      self::$mysqli_rw = new mysqli(
              self::getCfg('mysql')['read-write']['server'],
              self::getCfg('mysql')['read-write']['username'],
              self::getCfg('mysql')['read-write']['password'],
              self::getCfg('mysql')['read-write']['db']);
      self::$mysqli_rw->set_charset("utf8");
    }
    return self::$mysqli_rw;
  }
  
  /**
   * Get the named config file.
   * If not found, throws exception.
   * @param string $name
   * @return array
   * @throws NotFoundException;
   */
  static function getCfg($name) {
    if(isset(self::$json_cache[$name])) {
      return self::$json_cache[$name];
    }
    if(file_exists(dirname(__FILE__,3)."/cfg/$name.json")) {
      self::$json_cache[$name] = json_decode(file_get_contents(dirname(__FILE__,3)."/cfg/$name.json"), true);
      return self::$json_cache[$name];
    }
    throw new NotFoundException("Couldn't find config: $name");
  }
}