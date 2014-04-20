<?php
	 
function writelog(){
    $file = 'log.txt';
    // The new person to add to the file
    $line = time()."\t".$_SERVER['REMOTE_ADDR']."\t".basename($_SERVER['PHP_SELF'])."\t".$_SERVER['REQUEST_URI']."\n";
    file_put_contents($file, $line, FILE_APPEND);
}
 function dlUrl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
 }

function fourChanFormat($data){
    $returnArr = array();
    $returnArr['no'] = (int)$data['no'];
    $returnArr['now'] = date("m/d/y(D)H:i:s",$data['time']);
    $returnArr['name'] = $data['name'];
    $returnArr['com'] = $data['comment'];

    if($data['tim'] > 0){
        $returnArr['filename'] = $data['filename'];
        $returnArr['ext'] = $data['ext'];
        $returnArr['w'] = (int)$data['w'];
        $returnArr['h'] = (int)$data['h'];
        list($returnArr['tn_w'],$returnArr['tn_h']) = tn_Size($data['w'],$data['h']);
        $returnArr['tim'] = (int)$data['tim'];
    }
    $returnArr['time'] = (int)$data['time'];
    if($data['md5'] != ""){
        $returnArr['md5'] = $data['md5'];
        $returnArr['fsize'] = (int)$data['fsize'];
    }

    if($data['subject'] !="") $returnArr['sub'] = $data['subject'];
    if($data['trip'] !="") $returnArr['trip'] = $data['trip'];
    if($data['email'] !="") $returnArr['email'] = $data['email'];
    $returnArr['resto'] = $data['threadid'];
    $returnArr['id'] = $data['id'] != '' ? $data['id'] : $data['ns_id'] != '' ? $data['ns_id'] : '';

    if($data['no'] == $data['threadid']){
        $returnArr['bumplimit'] = 0;
        $returnArr['imagelimit'] = 0;
        $returnArr['replies'] = 0;
        $returnArr['images'] = 0;
    }
    return $returnArr;
}
 
function tn_Size($w,$h){
    if($w < 125 && $h < 125){
        return array($w,$h);
    }
    if($w > $h){
        $newWidth = 125;
        $newHeight = $h / ($w / 125);
    }
    else{
        $newHeight = 125;
        $newWidth = $w / ($h / 125);
    }
    return array((int)$newWidth,(int)$newHeight);
}
 
 /**
  * Returns the full name for a short-named thread type.
  * 
  * @param string $type the short thread-type name
  * @return string the long thread-type name 
  */
function threadType($type){
   $arr= array("animu"=>"Animu Thread",
                  "draw"=>"Draw Thread",
                  "gfur"=>"Gfur Thread",
                  "pony"=>"Pony Thread",
                  "sfur"=>"Sfur Thread",
                  "trap"=>"Trap Thread",
                  "loli"=>"Loli Thread",
                  "shota"=>"Shota Thread",
                  "ks"=>"Katawa Shoujo Thread");
    return $arr[$type];
}
 
 /**
  * Returns the current active NameSync threads in HTML format, similar to the 4chan
  * catalog page. Enclosed in a &lt;div&gt; with style from the .css
  * 
  * @param mysqli $dbl
  * @return string HTML code for threads 
  */
function getThreads(){
    $dbl = Config::getConnection();
   $q = $dbl->query("SELECT `thread`.`threadid`,`thread`.`type`,`thread`.`images`,`thread`.`replies`,`thread`.`nsnames`,`post`.`name`,`post`.`trip`,`post`.`w`,`post`.`h`,`post`.`md5`,`post`.`comment` FROM `thread` LEFT JOIN `post` ON `thread`.`threadid`=`post`.`no` WHERE `thread`.`active`=1 AND (`thread`.`nsnames`>1 OR `thread`.`type`<>'none') ORDER BY `thread`.`nsnames` DESC");
   while($row = $q->fetch_assoc()){
       $hover = $row['name']." ".$row['trip'];
       $hover .= ": ".substr(str_replace(array("'",'<br>',"<",'>'),'',$row['comment']),0,20)."...";
       $desired_height = 75;
       $typeStr = $row['type'] != "" ? str_replace(" Thread","",threadType($row['type'])) : "";
       if($row['h']!=0)
           $desired_width = floor($row['w'] * ($desired_height / $row['h']));
       else
           $desired_width=75;
       $ret .= "<div class='threadbox' style='width:{$desired_width}px'>$typeStr<br><a rel='noreferrer' href='http://boards.4chan.org/b/thread/{$row['threadid']}'><img title='$hover' alt='OP' align='middle' src='http://".Site::getThumbHostname()."/{$row['md5']}.jpg' style='width:{$desired_width}px; height:{$desired_height}px;'></a><span>R: {$row['replies']} I:{$row['images']} <a href='/itt/{$row['threadid']}'>N:{$row['nsnames']}</a></span></div>";
   }
   return $ret;
}
 
 /**
  * Returns a table of the most active posters in the past 30 minutes. Grouped by Name, not ID.
  * 
  * @param mysqli $dbl mysqli connection object
  * @return string table of all posters from the last 30 minutes
  */
function getPosters($dbl){
    $ret = "<table>";
    $ret .= "<tr><th>Name (!trip)</th><th>Posts</th></tr>";
    $q = $dbl->query("SELECT name,trip,dnt,COUNT(*) FROM `post` WHERE ((`name` <> 'Anonymous' AND `name` <> '') OR `trip` <> '' ) AND `time` > '".(time()-1800)."' GROUP BY CONCAT(`name`,`trip`) ORDER BY COUNT(*) DESC");
    while($row = $q->fetch_assoc()){
        if($row['dnt']==1 && !isAdmin()){
            $row['name']="Anonymous";
            $row['trip']="";
        }
        $ret .= "<tr><td>".$row['name']." ".$row['trip']."</td><td>".$row['COUNT(*)']."</td></tr>"; //<a href='name.php?n=".urlencode($row['name'])."'></a>
    }
    return $ret."</table>";
}

function isLoggedIn(){
    return isset($_SESSION['access']) ? $_SESSION['access']=="valid" : false; 
}

function isAdmin(){
    return isset($_SESSION['admin']) ? $_SESSION['admin'] : false; 
}

function isTerrance(){
    return isset($_SESSION['terrance']) ? $_SESSION['terrance'] : false;
}

function setServer($dbl,$nm, $val){
	$q = $dbl->prepare("INSERT INTO `server` (`name`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=?");
	$q->bind_param("sss",$nm,$val,$val);
	$q->execute();
}

function getServer($dbl,$nm){
	$q = $dbl->prepare("SELECT `value` FROM `server` WHERE `name`=?");
	$q->bind_param("s",$nm);
	$q->execute();
	$r = $q->get_result();
	return $r->fetch_assoc();
}

function human_filesize($bytes, $decimals = 0) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . @$sz[$factor].($factor>0?"B":"");
}

function secsToDHMS($seconds){
    $days = ($seconds > 60*60*24) ? (int)($seconds/(60*60*24)) : 0;
    $seconds -= $days*60*60*24;
    $hours = ($seconds > 60*60) ? (int)($seconds/(60*60)) : 0;
    $seconds -= $hours*60*60;
    $minutes = ($seconds > 60) ? (int)($seconds/60) : 0;
    $seconds -= $minutes*60;
    return [$days,$hours,$minutes,$seconds];
}