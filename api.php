<?php

include 'inc/config.php';
switch($_POST['a']){
    case 'post':
        $board = isset($_POST['b']) ? $_POST['b'] : 'b';
        $no = $_POST['id'];
        try{
            $post = new Post(Model::getPost($board, $no)->fetch_assoc(),$board);
            echo str_replace('data-original','src',$post->display());
        }
        catch(Exception $e){
            echo 'Error: '.$e->getMessage();
        }
        break;
}