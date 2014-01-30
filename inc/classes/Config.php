<?php
class Config {
       
    /** @var mysqli reference to mysqli object */
    static $mysqli;
    
    /** @var mysqli reference to read and write privileged mysql connection */
    static $mysqli_rw;
    
    /** @var array json configuration file, array */
    static $cfg;
    
    /** @var string[] json configuration file for mysql */
    static $sql_cfg;
    
    /** @return mysqli */
    static function getConnection(){
        if(self::$mysqli == null){
            self::$sql_cfg = json_decode(file_get_contents(dirname(__FILE__)."/../mysql.json"),true);
            self::$mysqli = new mysqli(self::$sql_cfg['read-only']['server'],self::$sql_cfg['read-only']['username'],self::$sql_cfg['read-only']['password'],self::$sql_cfg['read-only']['db']);
            self::$mysqli->set_charset("utf8");
        }
        return self::$mysqli;
    }
    
    static function closeConnection(){
        self::$mysqli->close();
        self::$mysqli = null;
    }
    
    /** @return mysqli */
    static function getConnectionRW(){
        if(self::$mysqli_rw == null){
            self::$sql_cfg = json_decode(file_get_contents(dirname(__FILE__)."/../mysql.json"),true);
            self::$mysqli_rw = new mysqli(self::$sql_cfg['read-write']['server'],self::$sql_cfg['read-write']['username'],self::$sql_cfg['read-write']['password'],self::$sql_cfg['read-write']['db']);
            self::$mysqli_rw->set_charset("utf8");
        }
        return self::$mysqli_rw;
    }
    
    static function closeConnectionRW(){
        self::$mysqli_rw->close();
        self::$mysqli_rw = null;
    }
    
    static function getCfg($key){
        if(self::$cfg == null){
            self::$cfg = json_decode(file_get_contents(dirname(__FILE__)."/../cfg.json"),true);
        }
        return isset(self::$cfg[$key]) ? self::$cfg[$key] : false;
    }
    
    static function setCfg($key,$value){
        //make sure we have the latest version of the config, for good measure.
        self::$cfg = json_decode(file_get_contents(dirname(__FILE__)."/../cfg.json"),true);
        self::$cfg[$key] = $value;
        file_put_contents(dirname(__FILE__)."../cfg.json",json_encode(self::$cfg));
    }
}