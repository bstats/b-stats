<?php

class User{
    private $uid;
    private $username;
    private $privilege;
    private $theme;
    
    function __construct($uid,$username,$privilege,$theme){
        $this->uid = (int)$uid;
        $this->username = $username;
        $this->privilege = (int)$privilege;
        $this->theme = $theme;
    }
    
    function getUID(){
        return $this->uid;
    }
    
    function getUsername(){
        return $this->username;
    }
    
    function getPrivilege(){
        return $this->privilege;
    }
    
    function getTheme(){
        return $this->theme;
    }
    
    function setTheme($theme){
        if(in_array($theme, ['yotsuba','tomorrow','yotsuba-pink','yotsuba-blue'])){
            if($this->uid != 0)
                Config::getConnectionRW()->query("UPDATE `users` SET `theme`='$theme' WHERE `uid`=$this->uid");
            $_SESSION['style'] = $theme;
            $this->theme = $theme;
        }
    }
    
    function canSearch(){
        return $this->privilege >= Site::LEVEL_SEARCH;
    }
}