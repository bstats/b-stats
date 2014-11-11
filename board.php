<?php
error_reporting(E_ALL);

/**
 * b-stats archive board viewer.
 * @author terrance
 * @version 0.03
 * changelog:
 * 0.01
 * -Initial POS board viewer.
 * 0.02
 * -Added a message for non-existent boards.
 * 0.03
 * -Now using the Board class.
 * 0.04
 * -Now using OOP more effectively
 */

include("inc/config.php");

try{
    $board = new Board($_GET['b']);
    
    $page = new Page("/".$board->getName()."/ - ".$board->getLongName(),"",$board->getPrivilege());
    $page->setBoard($board);

    /*
     * Get the correct index page.
     */
    $pageNo = isset($_GET['page']) ? $_GET['page'] : 1;
    if($board->isSwfBoard()){
        $pageNo = 1;
    }
    $threads = $board->getPage($pageNo);

    $page->appendToBody("<div class='board'>");

    /*
     * Special formatting just for /f/
     */
    if($board->isSwfBoard()){
        $page->appendToBody("<table class='flashListing' style='border:none;'>".
                            "<tbody>".
                            "<tr>".
                            "<td class='postblock'>No.</td><td class='postblock'>Name</td>".
                            "<td class='postblock'>File</td><td class='postblock'>Tag</td>".
                            "<td class='postblock'>Subject</td><td class='postblock'>Size</td>".
                            "<td class='postblock'>Date</td><td class='postblock'>Replies</td>".
                            "<td class='postblock'></td>".
                            "</tr>");
        foreach($threads as $thread){
          $thread->loadOP();
          $op = $thread->getPost(0);
            $tr = "<tr>".
                "<td>{$op->getNo()}</td>".
                "<td class='name-col'><span class='name'>{$op->getName()}</span>".($op->getTripcode() != '' ? " <span class='postertrip'>{$op->getTripcode()}</span>" : "")."</td>".
                "<td>[<a href='//images.b-stats.org/f/src/".$op->getMD5Filename().".swf' title='".str_replace("'","&#39;",$op->getFilename())."' data-width='{$op->getWidth()}' data-height='{$op->getHeight()}' target='_blank'>".(strlen($op->getFilename()) > 33 ? substr($op->getFilename(), 0,30)."(...)" : $op->getFilename())."</a>]</td>".
                "<td>[".str_replace("O","?",substr($thread->getTag(),0,1))."]</td>".
                "<td class='subject'><span title='".str_replace("'","&#39;",$op->getSubject())."'>".(strlen($op->getSubject()) > 33 ? substr($op->getSubject(), 0,30)."(...)" : $op->getSubject())."</span></td>".
                "<td>".human_filesize($op->getFilesize(),2)."</td>".
                "<td>".date("Y-m-d(D)H:i",$op->getTime())."</td>".
                "<td>{$thread->getChanPosts()}</td>".
                "<td>[<a href='thread/{$op->getNo()}'>View</a>]</td>".
                "</tr>";
            $page->appendToBody($tr);
        }
        $page->appendToBody("</tbody></table><br>");
    }
    /*
     * Standard formatting.
     */
    else{
        foreach($threads as $thread){
            $thread->loadOP();
            if($board->getName() == "b"){
              $thread->loadLastN(3);
            } else {
              $thread->loadLastN(5);
            }
            $page->appendToBody($thread->displayThread());
            $page->appendToBody("\n<hr>\n");
        }
        if($pageNo == 1){
            $linkList = file_get_contents(Site::getPath()."/htmls/pagelist/pagelist_first.html");
        }
        elseif(1 < $pageNo && $pageNo < $board->getPages() - 1){
            $linkList = file_get_contents(Site::getPath()."/htmls/pagelist/pagelist_middle.html");
        }
        else{
            $linkList = file_get_contents(Site::getPath()."/htmls/pagelist/pagelist_last.html");
        }
        $pages = "";
        for($p = 2; $p <= $board->getPages(); $p++){
            if($p == $pageNo)
                $pages .= "[<strong><a href='$p'>$p</a></strong>] ";
            else
                $pages .= "[<a href='$p'>$p</a>] ";
        }
        $page->appendToBody(str_replace(["_prev_","_next_","_pages_"],[$pageNo - 1, $pageNo + 1, $pages],$linkList));
    }
    $page->appendToBody("</div>");
    echo $page->display();
}
catch(Exception $e){
    $page = new Page("b-stats ERROR LOL","<div class='errorMsg'>Error: {$e->getMessage()}</div><br>",0);
    echo $page->display();
    exit;
}