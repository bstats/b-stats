<?php
class Thread {
    
    private $threadId;
    private $opId;
    private $posts;
    private $board;
    private $sticky;
    private $closed;
    
    /**
     * Thread constructor. (obvious, change this)
     * 
     * @param string|int $thrdId the thread ID/res number
     * @param string|int $op the thread ID/res number
     * @param string $board the thread's board
     */
    function __construct($thrdId, $op, $board, $sticky=false, $closed=false){
        $this->threadId = $thrdId;
        $this->opId = $op;
        $this->posts = array();
        $this->board = $board;
        $this->sticky = $sticky;
        $this->closed = $closed;
    }
    
    /**
     * Loads the entire thread from the DB
     * @todo implement this function
     * @return \Thread reference to self
     */
    function loadAll(){
        
        return $this;
    }
    
    /**
     * Loads only the OP. 
     * NB: Adds this on to the post stack, so only call this once!
     * @todo implement this function
     * @return \Thread reference to self
     */
    function loadOP(){
        
        return $this;
    }
    
    /**
     * Loads the last (n) posts from the DB
     * @param type $n
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
}