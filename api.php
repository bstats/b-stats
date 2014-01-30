<?php

include 'inc/config.php';
switch($_POST['a']){
    case "post":
        $board = isset($_POST['b']) ? $_POST['b'] : 'b';
        $no = $_POST['id'];
        $post = new Post(Model::getPost($board, $no)->fetch_assoc(),$board);
        echo str_replace("data-original","src",$post->display());
        break;
}