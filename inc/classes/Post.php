<?php

class_exists('HtmlElement');
/**
 * The Post class for rendering posts in html or json.
 * 
 * @todo Use getters and setters rather than public attributes.
 * @todo Use HTML fragment parsing rather than mixed PHP and HTML
 */
class Post implements JsonSerializable {

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
  private $owner;
  private $tag;
  public $dnt;
  public $backlinks;
  private $capcode;
  private $board;
  private $deleted;
  private $imgbanned;
  
  function __construct($no,$board='b'){
    if(is_array($no)){
      $arr = $no;
      $this->no = $arr['no'];
      $this->threadid = $arr['resto'];
      $this->time = $arr['time'];
      $this->tim = $arr['tim'];
      $this->id = (isset($arr['id']) && $arr['id'] != '') ? $arr['id'] :
                  (isset($arr['ns_id']) && $arr['ns_id'] != '' ? $arr['ns_id'] : "");
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
      $this->dnt = $arr['dnt'] ?? 0;
      $this->images = $arr['images'] ?? 0;
      $this->replies = $arr['replies'] ?? 0;
      $this->tag = $arr['tag'] ?? "";
      $this->deleted = $arr['deleted'];
      $this->capcode = $arr['capcode'];
    }
    if($this->md5 != '' && in_array(bin2hex(base64_decode(str_replace("-","/",$this->md5))),OldModel::getBannedHashes())){
      $this->imgbanned = true;
    }
    else{
      $this->imgbanned = false;
    }
    $this->owner = null;
    $this->backlinks = array();
    $this->board = (string)$board;
  }

  function setBoard($board){
    $this->board = (string)$board;
  }

  function hasImage(){
    return ($this->filename != "" && $this->md5 != null);
  }
  function getNo(){
    return $this->no;
  }
  function getThreadId(){
    return $this->threadid;
  }
  function getName(){
    return $this->name;
  }
  function getSubject(){
    return $this->sub;
  }
  function getTripcode(){
    return $this->trip;
  }
  function getEmail(){
    return $this->email;
  }
  function getFilesize(){
    return $this->fsize;
  }
  function getTag(){
    return $this->tag;
  }
  function getWidth(){
    return $this->w;
  }
  function getHeight(){
    return $this->h;
  }
  function getFilename(){
    return $this->filename;
  }
  function getExtension(){
    return $this->ext;
  }
  function getFullFilename(){
    return $this->filename.$this->ext;
  }
  function getMD5Filename(){
    return str_replace("/","-",$this->md5);
  }
  function getID(){
    return $this->id;
  }
  function getCapcode(){
    return $this->capcode;
  }
  function getTime(){
    return $this->time;
  }
  function isDeleted(){
    return $this->deleted == 1;
  }
  /**
   * Function for rendering the post.
   * 
   * @param string $t Either 'op', 'reply', or 'catalog' (default is reply)
   * @param boolean $sticky is the thread a sticky? default false (not sticky)
   * @param boolean $closed is the thread closed? default false (not closed)
   * @return string Post in HTML form
   */
  function display($t='reply',$sticky=false,$closed=false){
    if($t=='catalog'){
      list($tnW,$tnH) = tn_Size($this->w, $this->h);
      $md5Filename = str_replace("/","-",$this->md5);
      return "<div id='thread-{$this->no}' class='thread'>".
      "<a href='/{$this->board}/thread/{$this->no}'>".
      ($this->imgbanned ? 
              Site::parseHtmlFragment("banned_image.html")
              : "<img alt='' id='thumb-{$this->no}' class='thumb lazyload' width='$tnW' height='$tnH' data-original='//thumbs.b-stats.org/$md5Filename.jpg' data-id='{$this->no}'>").
      "</a>".
      ($this->replies>1? "<div title='(R)eplies / (I)mages' id='meta-{$this->no}' class='meta'>".
      "R: <b>{$this->replies}</b>".($this->images > 1 ? " / I: <b>{$this->images}</b>" : '').
      "</div>" : "").
      '<div class="teaser">'.
      "<b>{$this->sub}</b>".
      ($this->sub != ""? ": ".$this->com : $this->com).
      "</div></div>";

    }
    $timefmt = date("Y-m-d (D) H:i:s",$this->time - (3600 * 5));
    $comment = Yotsuba::fixHTML($this->com,$this->board,$this->threadid);

    /**
     * Add backlinks, a la 4chanX
     */
    $backlinkblck = "";
    foreach($this->backlinks as $bl){
        $backlinkblck .= "<a href='/{$this->board}/thread/{$this->threadid}#p$bl' data-board='$this->board' data-thread='{$this->threadid}' data-post='$bl' class='backlink'>&gt;&gt;$bl</a> ";
    }
    if($backlinkblck != ""){
        $backlinkblck = '<span class="container" id="blc'.$this->no.'">'.$backlinkblck.'</span>';
    }

    /**
     * Capcode formatting, for mods and admins, etc. per the 4chan API spec.
     */
    switch($this->capcode){
        case "mod":
            $nameBlockExtra = " capcodeMod";
            $cap = " <strong class='capcode'>## Mod</strong>".PHP_EOL;
            $cap .= '<img src="//s.4cdn.org/image/modicon.gif" alt="This user is a 4chan Moderator." title="This user is a 4chan Moderator." class="identityIcon">';
            break;
        case "admin":
        case "admin_highlight":
            $nameBlockExtra = " capcodeAdmin";
            $cap = " <strong class='capcode'>## Admin</strong>".PHP_EOL;
            $cap .= '<img src="/image/adminicon.gif" alt="This user is a 4chan Admin." title="This user is a 4chan Admin." class="identityIcon">';
            break;
        case "developer":
            $nameBlockExtra = " capcodeDeveloper";
            $cap = " <strong class='capcode'>## Developer</strong>".PHP_EOL;
            $cap .= '<img src="/image/developericon.gif" alt="This user is a 4chan Developer." title="This user is a 4chan Developer." class="identityIcon">';
            break;
        default:
            $nameBlockExtra = "";
            $cap = "";
    }

    /**
     * Tripcode and email formatting.
     */
    $nameblock = '<span class="nameBlock'.$nameBlockExtra.'">';
    $nametrip =  '<span class="name">'.$this->name.'</span>';
    if($this->trip != '')
        $nametrip .= '<span class="postertrip"> '.$this->trip.'</span>';
    if($this->email != '')
        $nameblock .=    '<a class="useremail" href="mailto:'.$this->email.'">'.$nametrip.'</a>';
    else
        $nameblock .= $nametrip;
    $nameblock .= $cap;

    if($this->id != "")
        $nameblock .= ' <span class="posteruid postNum id_'.$this->id.'">(ID: <a class="hand posteruid postNum" href="/'.$this->board.'/search/id/'.$this->id.'" title="View posts by this ID">'.$this->id.'</a>)</span>';

    $nameblock .= '</span>'; //closing nameBlock
    if($t === 'op')
      $ret =  <<<END
<div class="postContainer {$t}Container" id="pc{$this->no}">
<div id="p{$this->no}" class="post {$t}">
END;
    else
      $ret =  <<<END
<div class="postContainer {$t}Container" id="pc{$this->no}">
<div id="p{$this->no}" class="post {$t}">
END;

    $icons =($sticky ? PHP_EOL.'<img src="/image/sticky.gif" alt="Sticky" title="Sticky" class="stickyIcon">' : '').
            ($closed ? PHP_EOL.'<img src="/image/closed.gif" alt="Closed" title="Closed" class="closedIcon">' : '');

    $delet = ($this->deleted == 1) ? '<strong class="warning">[Deleted]</strong>' : '';
    $postinfo = <<<END
<div class="postInfo desktop" id="pi{$this->no}">
<input type="checkbox" name="{$this->no}" value="delete">$delet
<span class="subject">{$this->sub}</span>
$nameblock                    
<span class="dateTime" data-utc="{$this->time}">$timefmt</span>
<span class="postNum desktop">
<a href="#p{$this->no}" title="Highlight this post">No.</a>
<a href="/{$this->board}/thread/{$this->threadid}#p{$this->no}" title="Link to this post">{$this->no}</a>$icons
<a class='miniButton' href='javascript:' onclick='reportPost(this,"$this->board","$this->no","$this->threadid");'>Report</a>
</span>&nbsp;$backlinkblck
</div>
END;
    /**
     * Reply-styled posts, unlike OPs, have the post info above the fileinfo.
     */
    if($t=='reply')
      $ret .= $postinfo;

    /**
     * The following block is only for posts with an image attached.
     */
    if($this->md5 != "" && !$this->imgbanned){
      $md5code = urlencode($this->md5);
      $md5Filename = str_replace("/","-",$this->md5);
      $doublecode = urlencode($md5code);
      $humanFilesize = $this->fsize > 0 ? human_filesize($this->fsize).", ":"";
      list($thumbW,$thumbH) = tn_Size($this->w, $this->h);

      if($t == 'op' && ($this->w > 125 || $this->h > 125)){     //OP thumbs are 250x250 rather than 125x125
          $thumbW *= 2;
          $thumbH *= 2;
      }

      if($this->board == 'f'){ //There are no images on /f/. Only files.
          $thumb = "";
      }
      else {
          $thumb = "<a class='fileThumb' href='{$this->getImgUrl()}' target='_blank'>".
                   "<img class='lazyload' data-original='{$this->getThumbUrl()}' src='data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=' alt='' data-md5='{$this->md5}' data-md5-filename='$md5Filename' data-ext='{$this->ext}' data-full-img='{$this->getImgUrl()}' width='$thumbW' height='$thumbH' data-width='{$this->w}' data-height='{$this->h}' />".
                   "</a>";
      }
      $chanMedia = $this->board == 'f' ? '//i.4cdn.org/f/src/'.$this->filename.$this->ext : '//i.4cdn.org/'.$this->board.'/src/'.$this->tim.$this->ext;
      $fullImgLink = $this->board == 'f' ? $this->getSwfUrl() : $this->getImgUrl();
      $ret .= <<<END
<div id="f{$this->no}" class="file">
<div class="fileInfo">
    <span class="fileText" id="fT{$this->no}" data-filename="{$this->filename}{$this->ext}">
        <a class="imageLink" rel="noreferrer" href="$chanMedia" target="_blank" title="{$this->filename}{$this->ext}">{$this->filename}{$this->ext}</a>
            ($humanFilesize{$this->w}x{$this->h}, 
END;
      $ret .= $this->board == 'f' ? $this->tag.")" : "<span title='{$this->filename}{$this->ext}'>{$this->tim}{$this->ext}</span>) ";
      $ret .= a('iqdb',"http://iqdb.org/?url=http://thumbs.b-stats.org/{$md5code}.jpg")->set("target","_blank").'&nbsp;'
              .a('google',"http://www.google.com/searchbyimage?image_url=http://thumbs.b-stats.org/{$doublecode}.jpg")->set("target","_blank").'&nbsp;'
              .a('others',"/{$this->board}/search/md5/{$this->md5}")->set("target","_blank").'&nbsp;'
              .a('full',$fullImgLink)->set("target",'_blank');
      $ret .= <<<END
    </span>&nbsp;
</div>
$thumb
</div>
END;
    }
    if($this->imgbanned){
      $ret .= Site::parseHtmlFragment("banned_image.html");
    }

    /**
     * OPs, unlike reply-styled posts, have the post info below the fileinfo.
     */
    if($t=='op')
        $ret .= $postinfo;

    $ret .= <<<END
    <blockquote class="postMessage" id="m{$this->no}">
        $comment
    </blockquote>
</div>
</div>
END;
    return $ret;
  }
  
  /**
   * Returns the post as a PHP array, good for integrating into API calls.
   * @return array
   */
  function asArray(){
    $returnArr = [];
    $returnArr['no'] = (int)$this->no;
    $returnArr['now'] = date("m/d/y(D)H:i:s",$this->time);
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
    $thumbcfg = Config::getCfg("servers")["thumbs"];
    if($thumbcfg['https']){
      $url = 'https://'.$thumbcfg['httpshostname'].
              ($thumbcfg['httpsport'] != 443 ? ":".$thumbcfg['httpsport'] : "");
    }
    else {
      $url = 'http://'.$thumbcfg['hostname'].
              ($thumbcfg['port'] != 80 ? ":".$thumbcfg['port'] : "");
    }
    return $url.str_replace(['%hex%','%ext%'], 
                           [bin2hex($this->md5bin),$this->ext], 
                           $thumbcfg['format']);
  }
  function getImgUrl(){
    if(!$this->hasImage()){
      return "";
    }
    $imgcfg = Config::getCfg("servers")["images"];
    if($imgcfg['https']){
      $url = 'https://'.$imgcfg['httpshostname'].
              ($imgcfg['httpsport'] != 443 ? ":".$imgcfg['httpsport'] : "");
    }
    else {
      $url = 'http://'.$imgcfg['hostname'].
              ($imgcfg['port'] != 80 ? ":".$imgcfg['port'] : "");
    }
    return $url.str_replace(['%hex%','%ext%'], 
                           [bin2hex($this->md5bin),$this->ext], 
                           $imgcfg['format']);
  }
  
  function getSwfUrl(){
    if(!$this->hasImage()){
      return "";
    }
    $swfcfg = Config::getCfg("servers")["swf"];
    if($swfcfg['https']){
      $url = 'https://'.$swfcfg['httpshostname'].
              ($swfcfg['httpsport'] != 443 ? ":".$swfcfg['httpsport'] : "");
    }
    else {
      $url = 'http://'.$swfcfg['hostname'].
              ($swfcfg['port'] != 80 ? ":".$swfcfg['port'] : "");
    }
    return $url.str_replace(['%hex%','%ext%'], 
                           [bin2hex($this->md5bin),$this->ext], 
                           $swfcfg['format']);
  }
}