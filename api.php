<?php
include 'inc/config.php';
require_once 'inc/globals.php';
switch(get('a')){
    case 'post':
        $board = get('b');
        $no = get('id');
        try{
            $post = Model::get()->getPost(Model::get()->getBoard($board), $no);
            echo str_replace('data-original','src',$post->display());
        }
        catch(Exception $e){
            echo 'Error: '.$e->getMessage();
        }
        break;
    case 'boardInfo':
        if(!isset($_REQUEST['b'])) exit;
        header("Content-type: application/json");
        try{
            $board = Model::get()->getBoard($_REQUEST['b']);
            echo json_encode($board->getBoardInfo());
        }
        catch(Exception $ex){
            echo json_encode(array("error"=>"Could not load board info"));
        }
        break;
    case 'postLinks':
        if(!isset($_REQUEST['b'])) exit;
        $board = $_REQUEST['b'];
        $post = (int)$_REQUEST['p'];
        header("Content-type: application/json");
        echo json_encode(["posts"=>OldModel::getPostsWithLinkToPost($board, $post)]);
}