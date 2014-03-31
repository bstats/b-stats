<?php
class Thread implements Iterator {
    
    /**
     * @var int
     */
    private $threadId;
    /**
     * @var array posts 
     */
    private $posts;
    /**
     * @var Board
     */
    private $board;
    /**
     * @var bool
     */
    private $sticky;
    /**
     * @var bool
     */
    private $closed;
    
    /** @var int Current iterator index*/
    private $index;
    
    /**
     * Thread constructor. (obvious, change this)
     * 
     * @param string|int $thrdId the thread ID/res number
     * @param string|int $op the thread ID/res number
     * @param Board $board the thread's board
     * @param bool $sticky
     * @param bool $closed
     */
    function __construct($thrdId, $board, $sticky=false, $closed=false){
        if(!$board instanceof Board)
            throw new Exception("Board must be a Board object.");
        if(!is_numeric($thrdId))
            throw new Exception("Thread ID is invalid.");
        $this->threadId = $thrdId;
        $this->posts = array();
        $this->board = $board;
        $this->sticky = $sticky;
        $this->closed = $closed;
        
    }
    
    /**
     * Loads the entire thread from the DB.
     * Only works if no posts have been loaded yet.
     * @return \Thread reference to self
     */
    function loadAll(){
        if(count($this->posts) == 0){
            $tmp = Model::getThread($this->board, $this->threadId);
            while($row = $tmp[1]->fetch_assoc()){
                $this->addPost(new Post($row));
            }
        }
        else
        {
            throw new Exception("posts not 0");
        }
        return $this;
    }
    
    /**
     * Loads only the OP. If OP is already loaded, does nothing.
     * @return \Thread reference to self
     */
    function loadOP(){
        if(count($this->posts) == 0){
            
        }
        return $this;
    }
    
    /**
     * Loads the last (n) posts from the DB
     * @param type $n
     * @return \Thread reference to self
     */
    function loadLastN($n){
        $posts = Model::getLastNPosts($this->board->getName(), $this->threadId, $n);
        foreach($posts as $p){
            $this->addPost(new Post($p));
        }
        return $this;
    }
    
    /**
     * addPost
     * 
     * @param Post $post post to be added to the thread's array of posts.
     */
    function addPost($post){
        $post->setBoard($this->board);
        $this->posts[] = $post;
        $this->parseQuotes($post->com,$post->no);
    }
    
    /**
     * <code>Thread::parseQuotes</code> searches for inter-post links and adds backlinks to the respective posts.
     * 
     * @todo Use getters and setters rather than public attributes.
     * @todo Put this functionality into the b-stats native extension to save server resources.
     * @todo Better inline comments in this function.
     * @todo Show if backlinks are from (Dead) posts
     * @param string $com the post text to be searched
     * @param string|int $no the id of the post to be searched
     */
    function parseQuotes($com,$no)
    {
        $matches = array();
        $search = '~([a-z]+)link">&gt;&gt;(\d+)</~';
        preg_match_all($search,$com,$matches);
        for($i = 0; $i<count($matches[1]); $i++){
            $postno = $matches[2][$i];
            for($j=0;$j<count($this->posts);$j++){
                $p = $this->posts[$j];
                if($p->no == $postno){
                    if(!in_array($no,$p->backlinks)){
                        $this->posts[$j]->backlinks[]=$no;
                    }
                    break;
                }
            }
        }
    }
    
    /**
     * <code>Thread::displayThread</code> displays all the posts in a thread in 4chan style
     * 
     * @return string Thread in HTML form
     */
    function displayThread(){
        $ret = "<div class='thread' id='t".$this->threadId."'>";
        $op = array_shift($this->posts);
        $ret .= $op->display('op',$this->sticky,$this->closed);
        foreach($this->posts as $p){
            $ret .= "<div class='sideArrows'>&gt;&gt;</div>".$p->display();
        }
        $ret .= "</div>";
        return $ret;
    }
    /*
     * Iterator functions
     */
    function rewind(){
        $this->index = 0;
    }
    function valid(){
        return ($this->index < count($this->posts));
    }
    function key(){
        return $this->index;
    }
    /**
     * @return Post
     */
    function current(){
        return $this->posts[$this->index];
    }
    function next(){
        $this->index++;
    }
}