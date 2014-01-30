<?php 
class Site {
    const dir = "/var/www/archive";
    
    const LEVEL_TERRANCE = 9;
    const LEVEL_ADMIN = 3;
    const LEVEL_SEARCH = 2;
    const LEVEL_USER = 1;
    const LEVEL_GUEST = 0;
    
    static function isLoggedIn(){
        return isset($_SESSION['privilege']) ? $_SESSION['privilege'] > 0 : false; 
    }
    
    static function canSearch(){
        return isset($_SESSION['privilege']) ? $_SESSION['privilege'] >= self::LEVEL_SEARCH : false; 
    }
    
    static function isAdmin(){
        return isset($_SESSION['privilege']) ? $_SESSION['privilege'] >= self::LEVEL_ADMIN : false;  
    }

    static function isTerrance(){
        return isset($_SESSION['privilege']) ? $_SESSION['privilege'] === self::LEVEL_TERRANCE : false; 
    }
    
    /**
     * Check the current user's access level, using constants.
     * @return int User's access level.
     */
    static function checkPrivilege(){
        return (self::isLoggedIn() ? (self::isAdmin() ? (self::isTerrance() ? 
                self::LEVEL_TERRANCE : self::LEVEL_ADMIN) : self::LEVEL_USER) : self::LEVEL_GUEST);
    }
    
    static function setPrivilege($level){
        $_SESSION['privilege'] = $level;
        return true;
    }
    
    static function getUser(){
        return $_SESSION['user'] instanceof User ? $_SESSION['user'] : new User(0,"guest",0,"yotsuba");
    }
    
    static function getImageHostname(){
        return "images.b-stats.org";
    }
    static function getThumbHostname(){
        return "thumbs.b-stats.org";
    }
    static function getSiteHostname(){
        return $_SERVER['HTTP_HOST'];
    }
    static function getSiteProtocol(){
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https:' : 'http:';
    }
    static function parseHtmlFragment($filename,$search,$replace){
        $html = file_get_contents(self::dir."/htmls/$filename");
        return str_replace($search,$replace,$html);
    }
}