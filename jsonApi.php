<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
include('inc/config.php');

switch($_GET['a']){
    case "getPost":
        $no = $_GET['num'];
        $board = $_GET['board'];
        $data = fourChanFormat(Model::getPost($board, $no)->fetch_assoc());
        if(isset($_GET['foolfuuka']) && $_GET['foolfuuka'] == true){
            $data['fourchan_date'] = $data['now'];
            $data['timestamp'] = $data['time'];
            $data['name_processed'] = isset($data['name']) ? $data['name'] : "";
            $data['email_processed'] = isset($data['email']) ? $data['email'] : "";
            $data['trip_processed'] = isset($data['trip']) ? $data['trip'] : "";
            $data['poster_hash_processed'] = $data['id'];
            $data['poster_hash'] = $data['id'];
            $data['comment_sanitized'] = Yotsuba::sanitizeComment($data['com']);
            $data['comment'] = $data['comment_sanitized'];
            if(isset($data['w']) && $data['w'] > 0){
                $data['media']['op'] = $data['resto'] == 0 ? 1 : 0;
                $data['media']['preview_w'] = $data['tn_w'];
                $data['media']['preview_h'] = $data['tn_h'];
                $data['media']['media_filename'] = $data['filename'].$data['ext'];
                $data['media']['media_filename_processed'] = $data['filename'].$data['ext'];
                $data['media']['media_w'] = $data['w'];
                $data['media']['media_h'] = $data['h'];
                $data['media']['media_size'] = $data['fsize'];
                $data['media']['media_hash'] = $data['md5'];
                $data['media']['media_orig'] = $data['tim'].$data['ext'];
                $data['media']['media'] = $data['tim'].$data['ext'];
                //$data['media']['preview_op'] = $data['tim']."s.jpg";
                $data['media']['preview_reply'] = $data['tim']."s.jpg";
                $data['media']['remote_media_link'] = Site::getSiteProtocol()."//".Site::getImageHostname()."/".str_replace("/","-",$data['md5']).$data['ext'];
                $data['media']['media_link'] = Site::getSiteProtocol()."//".Site::getImageHostname()."/".str_replace("/","-",$data['md5']).$data['ext'];
                $data['media']['thumb_link'] = Site::getSiteProtocol()."//".Site::getThumbHostname()."/".$data['md5'].".jpg";
            }
            else{
                $data['media'] = null;
            }
            $data['comment_processed'] = $data['com'];
            $data['title'] = isset($data['sub']) ? $data['sub'] : "";
            $data['title_processed'] = $data['title'];
        }
        echo json_encode($data);
        break;
    case "getThread":
        $no = $_GET['num'];
        $board = $_GET['board'];
        list($thread,$data) = Model::getThread($board, $no);
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