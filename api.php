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
}