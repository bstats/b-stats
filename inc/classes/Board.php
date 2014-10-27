<?php

class Board {
    private $name;
    private $name_long;
    private $worksafe;
    private $pages;
    private $perpage;
    private $swf_board;
    private $archive;
    private $privilege;
    private $last_crawl;
    
    public function __toString() {
        return $this->name;
    }
    public function getName(){
        return $this->name;
    }
    public function getLongName(){
        return $this->name_long;
    }
    public function isWorksafe(){
        return (boolean)($this->worksafe);
    }
    public function isSwfBoard(){
        return (boolean)($this->swf_board);
    }
    public function getPages(){
        return $this->pages;
    }
    public function getThreadsPerPage(){
        return $this->perpage;
    }
    public function isArchive(){
        return $this->archive;
    }
    public function getPrivilege(){
        return $this->privilege;
    }
    public function getLastCrawl(){
        return $this->last_crawl;
    }
    
    public function getBoardInfo(){
        return ["shortname"=>$this->name,
                "longname"=>$this->name_long,
                "worksafe"=>$this->worksafe,
                "pages"=>$this->pages,
                "perpage"=>$this->perpage,
                "swf_board"=>$this->swf_board,
                "privilege"=>$this->privilege,
                "group"=>$this->group,
                "last_crawl"=>$this->last_crawl];
    }
    
    public function __construct($shortname) {
        $boardInfo = Model::getBoardInfo($shortname);
        if($boardInfo == false || $boardInfo['shortname'] !== $shortname)
            throw new Exception("Board does not exist");
        $this->name = $shortname;
        $this->name_long = $boardInfo['longname'];
        $this->worksafe = $boardInfo['worksafe'];
        $this->pages = $boardInfo['pages'];
        $this->perpage = $boardInfo['perpage'];
        $this->swf_board = $shortname === 'f' ? true : false;
        $this->privilege = $boardInfo['privilege'];
        $this->group = $boardInfo['group'];
        $this->last_crawl = $boardInfo['last_crawl'];
        $this->archive = true; //no `real` boards here. maybe in the future
    }
    
    public function getStats(){
        if(!isset($this->stats))
            $this->stats = new Stats($this->name);
        return $this->stats;
    }
    
    public function getThread($res,$deleted=false){
        return Model::getThread($this->name,$res,$deleted);
    }
    
    public function getPage($no){
        return Model::getPage($this,$no);
    }
    
    public static function getAllBoards(){
        $boards = Model::getBoards();
        foreach($boards as $boardinfo){
            $ret[] = new Board($boardinfo['shortname']);
        }
        return $ret;
    }
    public static function getBoardList(){
        $ret = "";
        $boards = Model::getBoards();
        $groups = array();
        foreach($boards as $board){
            $groups[$board['group']][] = $board;
        }
        foreach($groups as $group){
            $ret .= "[";
            $i = 0;
            foreach($group as $board){
                if($i++ > 0) $ret .= " / ";
                $ret .= '<a href="/'.$board['shortname'].'/" title="'.$board['longname'].'">'.$board['shortname'].'</a>';
            }
            $ret .= "] ";
        }
        return $ret;
    }
}