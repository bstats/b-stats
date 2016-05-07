<?php
// Global functions - use sparingly

/**
 * Strips all non-alphanumeric characters from a string.
 * @param string $str
 */
function alphanum(string $str) {
  return preg_replace("/[^a-zA-Z0-9]+/", "", $str);
}

/**
 * Throws an exception if the required post var is not set.
 * @param string $name name of the post var
 */
function post(string $name, $default = null) {
  if (!isset($_POST[$name])) {
    if($default !== null) {
      return $default;
    }
    throw new Exception("Required HTTP POST var not set: " . $name);
  }
  return $_POST[$name];
}

/**
 * Throws an exception if the required post var is not set.
 * @param string $name name of the post var
 */
function get(string $name, $default = null) {
  if (!isset($_GET[$name])) {
    if($default !== null) {
      return $default;
    }
    throw new Exception("Required HTTP GET var not set: " . $name);
  }
  return $_GET[$name];
}

function dlUrl($url,$fakeUA = false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    if($fakeUA)
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36");
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
  if($w == 0 || $h == 0) {
    return [0,0];
  } else if($w < 125 && $h < 125){
      return array($w,$h);
  }
  if($w > $h){
      $newWidth = 125;
      $newHeight = $h / ($w / 125);
  } else{
      $newHeight = 125;
      $newWidth = $w / ($h / 125);
  }
  return array((int)$newWidth,(int)$newHeight);
}

function human_filesize($bytes, $decimals = 0) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . $sz[(int)$factor].($factor>0?"B":"");
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

function durationToText($duration){
    $dhms = secsToDHMS($duration);
    $ret=array();
    if($dhms[0] != 0){ //days
        $ret[] = $dhms[0]." days";
    }
    if($dhms[1] != 0){ //hours
        $ret[] = $dhms[1]." hours";
    }
    if($dhms[2] != 0){ //minutes
        $ret[] = $dhms[2]." minutes";
    }
    if($dhms[3] != 0){
        $ret[] = $dhms[3]." seconds";
    }
    return implode(", ",$ret);
}

function ago($duration){
    if($duration < 5){
        return "just now";
    }
    else{
        return durationToText($duration)." ago";
    }
}

/**
 * Shorthand for making htmlelements.
 * @param string $tag
 * @param string $content
 * @param array $attrs
 * @return \View\HtmlElement
 */
function el($tag, $content="", $attrs=[]) {
  return new View\HtmlElement($tag,$content,$attrs);
}

/**
 * Makes a link
 * @param string $name
 * @param string $href
 * @return \View\HtmlElement
 */
function a($name, $href) {
  return new View\HtmlElement('a',$name,['href'=>$href]);;
}

/**
 * Makes a div
 * @param string $content
 * @param string $classes
 * @return \View\HtmlElement
 */
function div($content, $classes) {
  return new View\HtmlElement('div', $content, ['class'=>$classes]);
}

/**
 * Makes a span
 * @param string $content
 * @param string $classes
 * @return \View\HtmlElement
 */
function span($content, $classes) {
  return new View\HtmlElement('span', $content, ['class'=>$classes]);
}