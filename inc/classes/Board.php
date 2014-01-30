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
    
    public function getBoardInfo(){
        return ["board_shortname"=>$this->name,
                "board_longname"=>$this->name_long,
                "board_worksafe"=>$this->worksafe,
                "board_pages"=>$this->pages,
                "board_perpage"=>$this->perpage,
                "board_swf_board"=>$this->swf_board,
                "board_privilege"=>$this->privilege];
    }
    
    public function __construct($shortname) {
        $boardInfo = Model::getBoardInfo($shortname);
        if($boardInfo['board_shortname'] !== $shortname)
            throw new Exception("Board does not exist");
        $this->name = $shortname;
        $this->name_long = $boardInfo['board_longname'];
        $this->worksafe = $boardInfo['board_worksafe'];
        $this->pages = $boardInfo['board_pages'];
        $this->perpage = $boardInfo['board_perpage'];
        $this->swf_board = $shortname === 'f' ? true : false;
        $this->privilege = $boardInfo['board_privilege'];
        $this->archive = true; //no `real` boards here. maybe in the future
    }
    
    public function getStats(){
        if(!isset($this->stats))
            $this->stats = new Stats($this->name);
        return $this->stats;
    }
    
    public function getThread($res){
        return Model::getThread($this->name,$res);
    }
    
    public function getPage($no){
        return Model::getPage($this,$no);
    }
}