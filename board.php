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
    $pageResult = $board->getPage($pageNo);

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
        while($row = $pageResult->fetch_assoc()){
            $tr = "<tr>".
                "<td>{$row['no']}</td>".
                "<td class='name-col'><span class='name'>{$row['name']}</span>".($row['trip'] != '' ? " <span class='postertrip'>{$row['trip']}</span>" : "")."</td>".
                "<td>[<a href='//images.b-stats.org/f/src/".str_replace("/","-",$row['md5']).".swf' title='".str_replace("'","&#39;",$row['filename'])."' data-width='{$row['w']}' data-height='{$row['h']}' target='_blank'>".(strlen($row['filename']) > 33 ? substr($row['filename'], 0,30)."(...)" : $row['filename'])."</a>]</td>".
                "<td>[".str_replace("O","?",substr($row['tag'],0,1))."]</td>".
                "<td class='subject'><span title='".str_replace("'","&#39;",$row['subject'])."'>".(strlen($row['subject']) > 33 ? substr($row['subject'], 0,30)."(...)" : $row['subject'])."</span></td>".
                "<td>".human_filesize($row['fsize'],2)."</td>".
                "<td>".date("Y-m-d(D)H:i",$row['time'])."</td>".
                "<td>{$row['replies']}</td>".
                "<td>[<a href='thread/{$row['no']}'>View</a>]</td>".
                "</tr>";
            $page->appendToBody($tr);
        }
        $page->appendToBody("</tbody></table><br>");
    }
    /*
     * Standard formatting.
     */
    else{
        while($row = $pageResult->fetch_assoc()){
            $thread = new Thread($row['threadid'], $board , $row['sticky'], $row['closed']);
            $thread->addPost(new Post($row));
            $thread->loadLastN(3);
            $page->appendToBody($thread->displayThread());
            $page->appendToBody("\n<hr>\n");
        }
        if($pageNo == 1){
            $linkList = file_get_contents(Site::dir."/htmls/pagelist/pagelist_first.html");
        }
        elseif(1 < $pageNo && $pageNo < $board->getPages() - 1){
            $linkList = file_get_contents(Site::dir."/htmls/pagelist/pagelist_middle.html");
        }
        else{
            $linkList = file_get_contents(Site::dir."/htmls/pagelist/pagelist_last.html");
        }
        $pages = "";
        for($p = 2; $p < $board->getPages(); $p++){
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