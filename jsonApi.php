<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
include('inc/config.php');
include('inc/globals.php');
if(!isset($_GET['board']))
    die("no board specified");
switch($_GET['a']){
    case "getPost":
        $no = get('num');
        $board = Model::get()->getBoard(get('board'));
        try{
            $data = Model::get()->getPost($board,$no)->asArray();
        }
        catch(Exception $e){
            header("HTTP/1.1 404 Not Found");
            die(json_encode(["error"=>$e->getMessage()]));
        }
        echo json_encode($data);
        break;
    case "getThread":
        $no = $_GET['num'];
        $board = $_GET['board'];
        list($thread,$data) = OldModel::getThread($board, $no);
        $ret = array();
        $i=0;
        $thread = $thread->fetch_assoc();
        while($row = $data->fetch_assoc()){
            $fmt = fourChanFormat($row);
            if($i++ === 0){
                list($fmt['tn_h'],$fmt['tn_w'],$fmt['resto'])=array($fmt['tn_h']*2,$fmt['tn_w']*2,0);
                $fmt['bumplimit'] = (int)$thread['replies'] == 500 ? 1 : 0;
                $fmt['imagelimit'] = (int)$thread['images'] == 250 ? 1 : 0;
                $fmt['replies'] = (int)$thread['replies'];
                $fmt['images'] = (int)$thread['images'];
            }
            $ret[] = $fmt;
        }
        echo json_encode(array("posts"=>$ret));
        break;
}