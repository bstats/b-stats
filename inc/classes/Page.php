<?php
/**
 * The Page class contains functions for creating and displaying a page with consistent style.
 */
class Page {
    /**
     * The page's body
     * @var string
     */
    private $body;
    /**
     * The page's title.
     * @var string
     */
    private $title;
    /**
     * Access level for the page.
     * 0 = public, 1 = private, 2 = admin, 3 = terrance
     * @var int 
     */
    private $requiredLevel;
    
    /**
     * Access level of the user.
     * @var int 
     */
    private $userLevel;
    
    private $startTime;
    private $endTime;
    
    private $header;
    private $footer;
    private $board;
    private $addToHead;
    
    private $clearHeader;
    private $clearFooter;
    
    /**
     * Sets the text in the <code>&lt;title&gt;</code> tags.
     * @param string $title
     */
    function setTitle($title){
        $this->title = $title;
    }
    
    /**
     * @return string The page's title.
     */
    function getTitle(){
        return $this->title;
    }
    
    /**
     * Renders and returns the page.
     * @return string the page's entire HTML
     */
    function display(){
      if(!$this->clearFooter){
        $this->endTime = microtime(true);
        $this->appendToBody("<p style ='text-align:center'><small>page took ".
                round($this->endTime - $this->startTime, 4).
                " seconds to execute</small></p>");
        $this->footer = Site::parseHtmlFragment("pagefoot.html",["<!--copyright-->"],
                [file_get_contents("htmls/copyright.html")]);
      }
      else{
        $this->footer = file_get_contents("htmls/pagefoot.html");
      }
      $out = $this->header.$this->body.$this->footer;
        
      return $out;
    }
    /**
     * @return double Current page execution time
     */
    function getElapsedTime(){
        return round(microtime(true) - $this->startTime, 4);
    }
    function clearHead(){
      $this->clearHeader = true;
      $this->renderHeader();
    }
    function clearFoot(){
      $this->clearFooter = true;
    }
    /**
     * For pages that take a while to load.
     * @return string Page's header 
     */
    function getHeader(){
        return $this->header;
    }
    
    /**
     * For pages that take a while to load.
     * @return string Page's footer 
     */
    function getFooter(){
        return $this->footer;
    }
    
    /**
     * Replaces the page's body text.
     * @param string $html The html code to replace 
     */
    function setBody($html){
        $this->body = $html;
    }
    
    /**
     * Returns the body's HTML code.
     * Only includes what's within the
     * <code>&lt;div class='content'%gt;&lt;/div&gt;</code> tags, not including
     * the header.
     * 
     * @return string The body of the page
     */
    function getBody(){
        return $this->body;
    }
    
    /**
     * Adds text to the body. (inside <code>&lt;div class='content'%gt;&lt;/div&gt;</code>)
     * @param string $html HTML to add to the body
     */
    function appendToBody($html){
        $this->body .= $html;
    }
    
    /**
     * Add code to the <code>&lt;head%gt;</code> section of the code
     * @param string $html HTML code to add to the <code>&lt;head%gt;</code> section
     */
    function addToHead($html){
        $this->addToHead .= $html;
        $this->renderHeader();
    }
    
    /**
     * Gets the user's current privilege level.
     * @return int
     */
    function checkPrivilege(){
        return $this->user->getPrivilege();
    }
    
    /**
     * Sets the board name and description (use if the page is board-specific).
     * @param Board $board
     */
    function setBoard($board){
        if(is_object($board) && $board instanceof Board)
            $this->board = $board;
        else{
            try{
                $this->board = new Board($board);
            }
            catch(Exception $e){
                $this->board = null;
            }
        }
        $this->renderHeader();
    }
    
    function renderHeader(){
      if(!$this->clearHeader){
        $navBar = file_get_contents(Site::getPath()."/htmls/navbar.html");
        $navBarExtra = "";
        if($this->user->getPrivilege() == 0){ //If not logged in, show login form.
            $navBarExtra .= file_get_contents(Site::getPath()."/htmls/loginform.html");
        }
        
        if($this->user->getPrivilege() > 0){
            $extraButtons = "";
            if($this->user->getPrivilege() >= Site::LEVEL_ADMIN){
                $no = Model::getNumberOfReports();
                $reports = $no ? " ($no)" : "";
                $extraButtons .= "<span class='navelement'>[<a href = '/reportQueue.php'>Reports$reports</a>]</span>".PHP_EOL;
                $extraButtons .= "<span class='navelement'>[<a href = '/finderAdmin.php'>Finder Admin</a>]</span>".PHP_EOL;
            }
            if($this->user->getPrivilege() > Site::LEVEL_ADMIN){
                $extraButtons .= "<span class='navelement'>[<a href = '/scp.php'>SCP</a>]</span>".PHP_EOL;
            }
            if($this->user->canSearch())
                $extraButtons .= "<span class='navelement'>[<a href = '/advsearch.php'>Search</a>]</span>".PHP_EOL;
            
            $navBarExtra .= str_replace(['%username%','%privilege%','<!-- more buttons -->'],
                [$this->user->getUsername(),$this->user->getPrivilege(),$extraButtons],
                file_get_contents(Site::getPath()."/htmls/loginticker.html"));
            
        }
        $navBar = str_replace("<!--extra-->",$navBarExtra,$navBar);
      }
      else{
        $navBar = "";
      }
        
        $this->header = Site::parseHtmlFragment('pagehead.html',[
                '_stylename_','<!-- pageTitle -->',
                '<!-- additionalHeaders -->','<!-- navbar -->'],
               [$this->user->getTheme(),$this->title,
                $this->addToHead,$navBar]);
        if(!$this->clearHeader){
          if($this->board !== null){
            $boardTitle = "<hr><br><div class='boardTitle'>/".$this->board->getName()."/ - ".$this->board->getLongName()."</div>"
                    . "<a target='_blank' rel=noreferrer href='//boards.4chan.org/".$this->board->getName()."/'>View Board on 4chan</a>"
                    . "<hr>";
          } else {
            $boardTitle = "";
          }
          $this->header .= Site::parseHtmlFragment('pagebody.html', 
                ['<!-- boardTitle -->','<!-- boardlist -->'],
                [$boardTitle,Board::getBoardList()]);
        
        
          if($_SERVER['SCRIPT_NAME'] != "/index.php"){
            if($this->board == null) {
                $this->header .= "<div style='text-align:center'>[<a href='/index.php'>HOME</a>]</div>";
            } else {
                $this->header .= "<div style='position:relative; top: -20px;' id='topLinks'>[<a href='/index.php'>Home</a>]";
                if($_SERVER['SCRIPT_NAME'] != "/board.php")
                    $this->header .= " [<a href='/{$this->board->getName()}/'>Return</a>]";
                if($_SERVER['SCRIPT_NAME'] != "/catalog.php" && $this->board->isSwfBoard() != true)
                    $this->header .= " [<a href='/{$this->board->getName()}/catalog'>Catalog</a>]";
                $this->header .= "</div><br>";
            }
          }
        }
    }
    
    /**
     * Default constructor
     * @param string $title Text in the <code>&lt;title&gt;</code> tags.
     * @param string $body Initial body text.
     * @param int $privelege The minimum access level to see the page.
     */
    function __construct($title,$body="",$privilege=1,$board = null){
        $this->startTime = microtime(true);
        $this->title = $title;
        $this->body = $body;
        $this->requiredLevel = $privilege;
        $this->board = $board;
        $this->addToHead = "";
        $this->clearHeader = false;
        $this->clearFooter = false;
        $this->user = Site::getUser();
        if($this->user->getPrivilege() >= Site::LEVEL_SEARCH)
            $this->addToHead("<script type='text/javascript'>$(document).ready(function(){ImageHover.init('');});</script>");
        if($this->user->getPrivilege() >= Site::LEVEL_ADMIN)
            $this->addToHead("<script type='text/javascript' src='/script/bstats-admin.js'></script>");
        $this->renderHeader();
        if(Model::banned($_SERVER['REMOTE_ADDR'])){
            $_SESSION['banned'] = true;
            $banInfo = Model::getBanInfo($_SERVER['REMOTE_ADDR']);
            $expires = $banInfo['expires'] == 0 ?  "Never" : date("Y-m-d h:i:s T",$banInfo['expires']);
            $this->body = Site::parseHtmlFragment('banned.html', 
                ['__ip__','__reason__','__expires__'],
                [$_SERVER['REMOTE_ADDR'],$banInfo['reason'],$expires]);
            $this->title = "/b/ stats: ACCESS DENIED";
            echo $this->display();
            exit;
        }
        else $_SESSION['banned'] = false;
        if($this->user->getPrivilege() < $this->requiredLevel){
            $this->body = Site::parseHtmlFragment('accessDenied.html', 
                ['__privilege__','__required__'],
                [$this->user->getPrivilege(),$this->requiredLevel]);
            $this->title = "/b/ stats: ACCESS DENIED";
            echo $this->display();
            exit;
        }
        
    }
}

