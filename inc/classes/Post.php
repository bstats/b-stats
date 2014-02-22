<?php
/**
 * The Post class for rendering posts in html or json.
 * 
 * @todo Add json display option
 * @todo Use getters and setters rather than public attributes.
 * @todo Use HTML fragment parsing rather than mixed PHP and HTML
 */
class Post {

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
    private $filename;
    private $ext;
    public $com;
    private $w;
    private $h;
    private $owner;
    private $tag;
    public $dnt;
    public $backlinks;
    private $capcode;
    private $board;
    private $deleted;
    
    function __construct($no,$board='b'){
        if(is_array($no)){
            $arr = $no;
            $this->no = $arr['no'];
            $this->threadid = $arr['threadid'];
            $this->time = $arr['time'];
            $this->tim = $arr['tim'];
            $this->id = (isset($arr['id']) && $arr['id'] != '') ? $arr['id'] :
                        (isset($arr['ns_id']) && $arr['ns_id'] != '' ? $arr['ns_id'] : "");
            $this->name = $arr['name'];
            $this->email = $arr['email'];
            $this->sub = $arr['subject'];
            $this->trip = $arr['trip'];
            $this->md5 = $arr['md5'];
            $this->filename = $arr['filename'];
            $this->fsize = $arr['fsize'];
            $this->ext = $arr['ext'];
            $this->com = $arr['comment'];
            $this->w = $arr['w'];
            $this->h = $arr['h'];
            $this->dnt = isset($arr['dnt']) ? $arr['dnt'] : 0;
            $this->images = isset($arr['images']) ? $arr['images'] : 0;
            $this->replies = isset($arr['replies']) ? $arr['replies'] : 0;
            $this->tag = isset($arr['tag']) ? $arr['tag'] : "";
            $this->deleted = $arr['deleted'];
            $this->capcode = $arr['capcode'];
        }
        $this->owner = null;
        $this->backlinks = array();
        $this->board = (string)$board;
    }

    function setBoard($board){
        $this->board = (string)$board;
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
            $comTrimmed = Yotsuba::sanitizeComment($this->com);
            list($tnW,$tnH) = tn_Size($this->w, $this->h);
            $md5Filename = str_replace("/","-",$this->md5);
            return "<div id='thread-{$this->no}' class='thread'>".
            "<a href='/{$this->board}/res/{$this->no}'>".
            "<img alt='' id='thumb-{$this->no}' class='thumb lazyload' width='$tnW' height='$tnH' data-original='//thumbs.b-stats.org/$md5Filename.jpg' data-id='{$this->no}'>".
            "</a>".
            ($this->replies>1? "<div title='(R)eplies / (I)mages' id='meta-{$this->no}' class='meta'>".
            "R: <b>{$this->replies}</b>".($this->images > 1 ? " / I: <b>{$this->images}</b>" : '').
            "</div>" : "").
            '<div class="teaser">'.
            "<b>{$this->sub}</b>".
            ($this->sub != ""? ": ".$comTrimmed : $comTrimmed).
            "</div></div>";
            
        }
        $timefmt = date("Y-m-d (D) H:i:s",$this->time);
        $comment = Yotsuba::fixHTML($this->com,$this->board);
        
        /**
         * Add backlinks, a la 4chanX
         */
        $backlinkblck = "";
        foreach($this->backlinks as $bl){
            $backlinkblck .= "<a href='/{$this->board}/res/{$this->threadid}#p$bl' data-board='$this->board' data-thread='{$this->threadid}' data-post='$bl' class='backlink'>&gt;&gt;$bl</a> ";
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
<a class="anchor" id="p{$this->no}"></a><div id="p{$this->no}" class="post {$t}">
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
<a href="/{$this->board}/res/{$this->threadid}#p{$this->no}" title="Link to this post">{$this->no}</a>$icons
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
        if($this->md5 != ""){
            $md5code = urlencode($this->md5);
            $md5Filename = str_replace("/","-",$this->md5);
            $doublecode = urlencode($md5code);
            $humanFilesize = $this->fsize > 0 ? human_filesize($this->fsize).", ":"";
            list($thumbW,$thumbH) = tn_Size($this->w, $this->h);
            
            if($t == 'op'){     //OP thumbs are 250x250 rather than 125x125
                $thumbW *= 2;
                $thumbH *= 2;
            }
            
            if($this->board == 'f'){ //There are no images on /f/. Only files.
                $thumb = "";
            }
            else {
                $thumb = "<a class='fileThumb' "."href='//images.b-stats.org/$md5Filename{$this->ext}' target='_blank'>".
                         "<img class='lazyload' data-original='//thumbs.b-stats.org/$md5Filename.jpg' alt='img' data-md5='{$this->md5}' data-md5-filename='$md5Filename' data-ext='{$this->ext}' width='$thumbW' height='$thumbH' data-width='{$this->w}' data-height='{$this->h}' />".
                         "</a>";
            }
            $chanMedia = $this->board == 'f' ? '//i.4cdn.org/f/src/'.$this->filename.$this->ext : '//i.4cdn.org/'.$this->board.'/src/'.$this->tim.$this->ext;
            $fullImgLink = $this->board == 'f' ? "//images.b-stats.org/f/src/{$this->md5}.swf" : "//images.b-stats.org/{$this->md5}{$this->ext}";
            $ret .= <<<END
<div id="f{$this->no}" class="file">
<div class="fileInfo">
    <span class="fileText" id="fT{$this->no}" data-filename="{$this->filename}{$this->ext}">
        <a class="imageLink" rel="noreferrer" href="$chanMedia" target="_blank" title="{$this->filename}{$this->ext}">{$this->filename}{$this->ext}</a>
            ($humanFilesize{$this->w}x{$this->h}, 
END;
            $ret .= $this->board == 'f' ? $this->tag.")" : "<span title='{$this->filename}{$this->ext}'>{$this->tim}{$this->ext}</span>)";
            $ret .= <<<END
            <a target='_blank' title='iqdb image search' href='http://iqdb.org/?url=http://thumbs.b-stats.org/{$md5code}.jpg'>iqdb</a>&nbsp;<a target='_blank' title='Reverse Google Image Search' href='http://www.google.com/searchbyimage?image_url=http://thumbs.b-stats.org/{$doublecode}.jpg'>google</a>&nbsp;<a target='_blank' title='Other posts with this image' href='/{$this->board}/search/md5/{$this->md5}'>others</a>&nbsp;<a target='_blank' title='Full image (archive)' href='$fullImgLink'>full</a>
    </span>&nbsp;
</div>
$thumb
</div>
END;
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
}