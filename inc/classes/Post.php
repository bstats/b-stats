<?php

class_exists('HtmlElement');
/**
 * The Post class for rendering posts in html or json.
 * 
 * @todo Use getters and setters rather than public attributes.
 * @todo Use HTML fragment parsing rather than mixed PHP and HTML
 */
class Post implements JsonSerializable {

  private $doc_id;
  public $no;
  public $threadid;
  public $time;
  private $tim;
  private $id;
  private $name;
  private $email;
  private $sub;
  private $trip;
  private $md5;
  private $md5bin;
  private $filename;
  private $ext;
  public $com;
  private $fsize;
  private $w;
  private $h;
  private $tn_w;
  private $tn_h;
  private $owner;
  private $tag;
  public $dnt;
  public $backlinks;
  private $capcode;
  /** @var Board */
  private $board;
  private $deleted;
  private $filedeleted;
  private $imgbanned;
  
  function __construct($no, Board $board){
    if(is_array($no)){
      $arr = $no;
      $this->doc_id = $arr['doc_id'] ?? 0;
      $this->no = $arr['no'];
      $this->threadid = $arr['resto'];
      $this->time = $arr['time'];
      $this->tim = $arr['tim'] ?? 0;
      $this->id = (isset($arr['id']) && $arr['id'] != '') ? $arr['id'] :
                  (isset($arr['ns_id']) && $arr['ns_id'] != '' ? $arr['ns_id'] : null);
      $this->name = $arr['name'];
      $this->email = $arr['email'];
      $this->sub = $arr['sub'];
      $this->trip = $arr['trip'];
      $this->com = $arr['com'];
      $this->md5 = base64_encode($arr['md5']) ?? null;
      $this->md5bin = $arr['md5'] ?? null;
      $this->filename = $arr['filename'] ?? "";
      $this->fsize = $arr['fsize'] ?? 0;
      $this->ext = $arr['ext'] ?? "";
      $this->w = $arr['w'] ?? 0;
      $this->h = $arr['h'] ?? 0;
      list($this->tn_w, $this->tn_h) = tn_Size($this->w, $this->h);
      $this->dnt = $arr['dnt'] ?? 0;
      $this->images = $arr['images'] ?? 0;
      $this->replies = $arr['replies'] ?? 0;
      $this->tag = $arr['tag'] ?? "";
      $this->deleted = $arr['deleted'];
      $this->capcode = $arr['capcode'];
      $this->filedeleted = $arr['filedeleted'];
    }
    if($this->md5 != '' && in_array(bin2hex(base64_decode(str_replace("-","/",$this->md5))),OldModel::getBannedHashes())){
      $this->imgbanned = true;
    }
    else{
      $this->imgbanned = false;
    }
    $this->owner = null;
    $this->backlinks = array();
    $this->board = $board;
  }

  function setBoard(Board $board){
    $this->board = $board;
  }

  function hasImage(){
    return ($this->filename != "" && $this->md5 != null);
  }
  function hasComment() {
    return $this->com != null;
  }
  function getDocId():int {
    return $this->doc_id;
  }
  function getComment():string {
    return $this->com ?? "";
  }
  function getNo():int{
    return $this->no;
  }
  function getThreadId():int{
    return $this->threadid;
  }
  function getName():string{
    return $this->name ?? '';
  }
  function getSubject():string{
    return $this->sub ?? '';
  }
  function getTripcode():string{
    return $this->trip ?? '';
  }
  function getEmail():string{
    return $this->email ?? '';
  }
  function getFilesize():int{
    return $this->fsize;
  }
  function getTag():string{
    return $this->tag;
  }
  function getWidth():int{
    return $this->w;
  }
  function getHeight():int{
    return $this->h;
  }
  function getThumbWidth():int {
    return $this->tn_w;
  }
  function getThumbHeight():int {
    return $this->tn_h;
  }
  function getFilename():string{
    return $this->filename;
  }
  function getExtension():string{
    return $this->ext;
  }
  function getFullFilename():string{
    return $this->filename.$this->ext;
  }
  function getMD5Filename():string{
    return str_replace("/","-",$this->md5);
  }
  function getMD5Bin():string {
    return $this->md5;
  }
  function getMD5Hex():string {
    return bin2hex($this->md5bin);
  }
  function getID():string{
    return $this->id ?? '';
  }
  function getCapcode():string{
    return $this->capcode;
  }
  function getTime():int{
    return $this->time;
  }
  function getTim():int {
    return $this->tim;
  }
  function isDeleted():bool{
    return $this->deleted == 1;
  }
  function isFileDeleted():bool {
    return $this->filedeleted == 1;
  }
  
  function getChanTime():string {
    return date("m/d/y(D)H:i:s",$this->time);
  }
  /**
   * Returns the post as a PHP array, good for integrating into API calls.
   * @return array
   */
  function asArray(){
    $returnArr = [];
    $returnArr['no'] = (int)$this->no;
    $returnArr['now'] = $this->getChanTime();
    $returnArr['time'] = (int)$this->time;
    $returnArr['name'] = $this->name;
    $returnArr['com'] = $this->com;
    if($this->tim > 0 && !$this->imgbanned){
      $returnArr['filename'] = $this->filename;
      $returnArr['ext'] = $this->ext;
      $returnArr['w'] = (int)$this->w;
      $returnArr['h'] = (int)$this->h;
      list($returnArr['tn_w'],$returnArr['tn_h']) = tn_Size($this->w,$this->h);
      $returnArr['tim'] = (int)$this->tim;
      $returnArr['md5'] = str_replace("-","/",$this->md5);
      $returnArr['md5_hex'] = bin2hex($this->md5bin);
      $returnArr['fsize'] = (int)$this->fsize;
    }
    if($this->sub !=""){
      $returnArr['sub'] = $this->sub;
    }
    if($this->trip !=""){
      $returnArr['trip'] = $this->trip;
    }
    if($this->email !=""){
      $returnArr['email'] = $this->email;
    }
    if($this->id != ''){
      $returnArr['id'] = $this->id;
    }
    $returnArr['resto'] = (int)$this->threadid;
    if($this->no == $this->threadid){
        $returnArr['bumplimit'] = 0;
        $returnArr['imagelimit'] = 0;
        $returnArr['replies'] = 0;
        $returnArr['images'] = 0;
    }
    return $returnArr;
  }
  
  /*
   * JsonSerializable implementation
   */
  public function jsonSerialize() {
    return $this->asArray();
  }
  
  /**
   * Returns the post as a JSON object.
   * @return string
   */
  function asJsonString(){
    return json_encode($this->asArray());
  }
  
  function getThumbUrl(){
    if(!$this->hasImage()){
      return "";
    }
    $thumbcfg = $this->ext == '.swf' 
            ? Config::getCfg("servers")["swfthumbs"]
            : Config::getCfg("servers")["thumbs"];
    if($thumbcfg['https']){
      $url = 'https://'.$thumbcfg['httpshostname'].
              ($thumbcfg['httpsport'] != 443 ? ":".$thumbcfg['httpsport'] : "");
    }
    else {
      $url = 'http://'.$thumbcfg['hostname'].
              ($thumbcfg['port'] != 80 ? ":".$thumbcfg['port'] : "");
    }
    $md5Hex = bin2hex($this->md5bin);
    return $url.str_replace(['%hex%','%ext%','%1%','%2%'], 
                           [$md5Hex,$this->ext, $md5Hex[0], $md5Hex[1]], 
                           $thumbcfg['format']);
  }
  function getImgUrl(){
    if(!$this->hasImage()){
      return "";
    }
    $imgcfg = $this->ext == '.swf' 
            ? Config::getCfg("servers")["swf"]
            : Config::getCfg("servers")["images"];
    if($imgcfg['https']){
      $url = 'https://'.$imgcfg['httpshostname'].
              ($imgcfg['httpsport'] != 443 ? ":".$imgcfg['httpsport'] : "");
    }
    else {
      $url = 'http://'.$imgcfg['hostname'].
              ($imgcfg['port'] != 80 ? ":".$imgcfg['port'] : "");
    }
    $md5Hex = bin2hex($this->md5bin);
    return $url.str_replace(['%hex%','%ext%','%1%','%2%'], 
                           [$md5Hex,$this->ext, $md5Hex[0], $md5Hex[1]], 
                           $imgcfg['format']);
  }
  
  function getSwfUrl(){
    return $this->getImgUrl();
  }
  
  public function __get($name) {
    if(isset($this->$name)) {
      return $this->$name;
    }
    return null;
  }
}