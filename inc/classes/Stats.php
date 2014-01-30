<?php
class Stats{
    static $cfg;
    
    static function getStat($key){
        if(self::$stats == null){
            self::$stats = json_decode(file_get_contents(dirname(__FILE__)."/../stats.json"),true);
        }
        return isset(self::$stats[$key]) ? self::$stats[$key] : false;
    }
    
    static function setStat($key,$value){
        //make sure we have the latest version of the stats file, for good measure.
        self::$stats = json_decode(file_get_contents(dirname(__FILE__)."/../stats.json"),true);
        self::$stats[$key] = $value;
        file_put_contents(dirname(__FILE__)."../stats.json",json_encode(self::$stats));
    }
    
    public function __construct($board){
        $this->posts = getStat($board."_posts");
        $this->threads = getStat($board."_threads");
        $this->mostPosts = getStat($board."_longest_thread_posts");
        $this->mostTime = getStat($board."_longest_thread_time");
        $this->mostActive = getStat($board."_most_active_poster");
        $this->flashes = getStat("flashes");
        $this->flashData = getStat("flashsize");
        $this->thumbs = getStat("thumbs");
        $this->thumbData = getStat("thumbsize");
        $this->images = getStat("fulls");
        $this->imageData = getStat("fullsize");
    }
    
    public function getNumberOfPosts(){
        return $this->posts;
    }
    public function getNumberOfThreads(){
        return $this->threads;
    }
    public function getLongestThreadByPosts(){
        return $this->mostPosts;
    }
    public function getLongestThreadByTime(){
        return $this->mostTime;
    }
    public function getMostActivePoster(){
        return $this->mostActive;
    }
    public function getNumberOfThumbs(){
        return $this->thumbs;
    }
    public function getNumberOfImages(){
        return $this->images;
    }
    public function getNumberOfFlashes(){
        return $this->flashes;
    }
    public function getThumbDataSize(){
        return $this->thumbData;
    }
    public function getImageDataSize(){
        return $this->imageData;
    }
    public function getFlashDataSize(){
        return $this->flashData;
    }
}