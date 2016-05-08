<?php
namespace View;

use DateTime;
use DateTimeZone;
use ImageBoard\Post;
use ImageBoard\Board;
use ImageBoard\Thread;
use ImageBoard\Yotsuba;
use Site\Site;

/**
 * May be made non-static in the future.
 */
class PostRenderer
{
  const TIME_FORMAT = "m/d/y(D)H:i:s";
  const DISPLAY_OP = "op";
  const DISPLAY_REPLY = "reply";
  const DISPLAY_CATALOG = "catalog";

  static function renderPost(Post $post, $display = PostRenderer::DISPLAY_REPLY, bool $sticky = false, bool $closed = false):string
  {
    if ($display == self::DISPLAY_CATALOG) {
      list($tnW, $tnH) = tn_Size($post->w, $post->h);
      return "<div id='thread-{$post->no}' class='thread'>" .
      "<a href='/{$post->getBoard()}/thread/{$post->getNo()}'>" .
      ($post->imgbanned ?
          Site::parseHtmlFragment("banned_image.html")
          : "<img alt='' id='thumb-{$post->no}' class='thumb' width='$tnW' height='$tnH' src='{$post->getThumbUrl()}' data-id='{$post->no}'>") .
      "</a>" .
      ($post->replies > 0 ? "<div title='(R)eplies / (I)mages' id='meta-{$post->no}' class='meta'>" .
          "R: <b>{$post->replies}</b>" . ($post->images > 0 ? " / I: <b>{$post->images}</b>" : '') .
          "</div>" : "") .
      '<div class="teaser">' .
      "<b>{$post->sub}</b>" .
      ($post->sub != "" ? ": " . $post->com : $post->com) .
      "</div></div>";
    }
    $postDiv = div('', 'post ' . $display)->set('id', 'p' . $post->no);

    if ($display == self::DISPLAY_REPLY) {
      // Replies have postInfo first, then imageblock
      $postDiv->append(self::makePostInfo($post, $sticky, $closed));
      $postDiv->append(self::makeImageBlock($post, $display));
    } else if ($display == self::DISPLAY_OP) {
      // OPs have imageblock first, then post info
      $postDiv->append(self::makeImageBlock($post, $display));
      $postDiv->append(self::makePostInfo($post, $sticky, $closed));
    }
    $postDiv->append(el('blockquote',
        self::fixHTML($post),
        ['class' => 'postMessage', 'id' => "m$post->no"]));

    return div($postDiv, "postContainer {$display}Container")->set('id', "pc$post->no");
  }

  private static function makePostInfo(Post $post, bool $sticky, bool $closed)
  {
    $icons = ($sticky ? PHP_EOL . '<img src="/image/sticky.gif" alt="Sticky" title="Sticky" class="stickyIcon">' : '') .
        ($closed ? PHP_EOL . '<img src="/image/closed.gif" alt="Closed" title="Closed" class="closedIcon">' : '');
    $delet = ($post->deleted == 1) ? '<strong class="warning">[Deleted]</strong>' : '';
    if ($post->isFileDeleted()) {
      $delet .= '<strong class="warning">[File Deleted]</strong>';
    }
    $time = new DateTime("now", new DateTimeZone("America/New_York"));
    $time->setTimestamp($post->time);
    return div('', 'postInfo desktop')->set('id', 'pi' . $post->no)
        ->append("<input type='checkbox' name='{$post->no}' value='delete'>$delet")
        ->append(span($post->sub, 'subject') . ' ')
        ->append(self::makeNameBlock($post) . ' ')
        ->append(span($time->format(self::TIME_FORMAT), 'dateTime')->set('data-utc', $post->getTime()) . ' ')
        ->append(span('', 'postNum desktop')
            ->append(a('No.', "#p{$post->getNo()}")->set('title', 'Highlight this post'))
            ->append(a($post->getNo(), "/{$post->board}/thread/{$post->getThreadId()}#p{$post->getNo()}")->set('title', 'Link to this post'))
            ->append($icons . ' ')
            ->append(a('Report', 'javascript:')->set('class', 'miniButton')->set('onclick', "reportPost(this,'$post->board','$post->no','$post->threadid');")))
        ->append(' ' . self::makeBackLinks($post));
  }

  private static function makeImageBlock(Post $post, $display)
  {
    /**
     * The following block is only for posts with an image attached.
     */
    if ($post->md5 != "" && !$post->imgbanned) {
      $md5Filename = str_replace('/', '-', $post->md5);
      $humanFilesize = $post->fsize > 0 ? human_filesize($post->fsize) . ", " : "";
      list($thumbW, $thumbH) = tn_Size($post->w, $post->h);

      if ($display == self::DISPLAY_OP && ($post->w > 125 || $post->h > 125)) {     //OP thumbs are 250x250 rather than 125x125
        $thumbW *= 2;
        $thumbH *= 2;
      }

      $thumb = "<a class='fileThumb' href='{$post->getImgUrl()}' target='_blank'>" .
          "<img src='{$post->getThumbUrl()}' alt='' data-md5='{$post->md5}' data-md5-filename='$md5Filename' data-ext='{$post->ext}' data-full-img='{$post->getImgUrl()}' width='$thumbW' height='$thumbH' data-width='{$post->w}' data-height='{$post->h}' />" .
          "</a>";

      $chanMedia = $post->board == 'f' ? '//i.4cdn.org/f/src/' . $post->filename . $post->ext : '//i.4cdn.org/' . $post->board . '/src/' . $post->tim . $post->ext;
      $fullImgLink = $post->getExtension() == '.swf' ? $post->getSwfUrl() : $post->getImgUrl();
      $fileDiv = div('', 'file')->set('id', 'f' . $post->no);
      $fileInfo = div('', 'fileInfo');
      $fileText = span('', 'fileText')->set('id', 'fT' . $post->no)->set('data-filename', $post->filename . $post->ext);
      $fileText
          ->append(
              a($post->filename . $post->ext, $chanMedia)
                  ->set("target", "_blank")
                  ->set("title", $post->filename . $post->ext)
                  ->set("class", 'imageLink')
                  ->set('rel', 'noreferrer'))
          ->append('&nbsp;')
          ->append("($humanFilesize{$post->w}x{$post->h}, " . ($post->board == 'f' ? $post->tag . ")" : "<span title='{$post->filename}{$post->ext}'>{$post->tim}{$post->ext}</span>)&nbsp;"));
      if ($post->getExtension() != '.swf') {
        $fileText->append(a('iqdb', "http://iqdb.org/?url={$post->getThumbUrl()}")->set("target", "_blank") . '&nbsp;'
            . a('google', "http://www.google.com/searchbyimage?image_url={$post->getThumbUrl()}")->set("target", "_blank"));
      }
      $fileText->append('&nbsp;' . a('reposts', "/{$post->board}/search/md5/{$post->getMD5Hex()}")->set("target", "_blank") . '&nbsp;'
          . a('full', $fullImgLink)->set("target", '_blank'));
      $fileInfo->append($fileText);
      $fileDiv->append($fileInfo);
      $fileDiv->append($thumb);
      return $fileDiv;
    } else if ($post->imgbanned) {
      return Site::parseHtmlFragment("post/banned_image.html");
    } else {
      return "";
    }
  }

  private static function makeBackLinks(Post $post)
  {
    $backlinkblck = "";
    foreach ($post->backlinks as $bl) {
      $backlinkblck .= a('&gt;&gt;' . $bl, "/{$post->board}/thread/{$post->threadid}#p$bl")
              ->set('data-board', $post->board)->set('data-thread', $post->threadid)
              ->set('data-post', $bl)->set('class', 'backlink') . ' ';
    }
    if ($backlinkblck != "") {
      $backlinkblck = span($backlinkblck, 'container')->set('id', "blc$post->no");
    }
    return $backlinkblck;
  }

  private static function makeNameBlock(Post $post)
  {
    /**
     * Capcode formatting, for mods and admins, etc. per the 4chan API spec.
     */
    switch ($post->capcode) {
      case "mod":
        $nameBlockExtra = " capcodeMod";
        $cap = " <strong class='capcode'>## Mod</strong>" . PHP_EOL;
        $cap .= '<img src="/image/modicon.gif" alt="This user is a 4chan Moderator." title="This user is a 4chan Moderator." class="identityIcon">';
        break;
      case "admin":
      case "admin_highlight":
        $nameBlockExtra = " capcodeAdmin";
        $cap = " <strong class='capcode'>## Admin</strong>" . PHP_EOL;
        $cap .= '<img src="/image/adminicon.gif" alt="This user is a 4chan Admin." title="This user is a 4chan Admin." class="identityIcon">';
        break;
      case "developer":
        $nameBlockExtra = " capcodeDeveloper";
        $cap = " <strong class='capcode'>## Developer</strong>" . PHP_EOL;
        $cap .= '<img src="/image/developericon.gif" alt="This user is a 4chan Developer." title="This user is a 4chan Developer." class="identityIcon">';
        break;
      case "manager":
        $nameBlockExtra = " capcodeManager";
        $cap = " <strong class='capcode'>## Manager</strong>" . PHP_EOL;
        $cap .= '<img src="/image/managericon.gif" alt="This user is a 4chan Manager." title="This user is a 4chan Manager." class="identityIcon">';
        break;
      default:
        $nameBlockExtra = "";
        $cap = "";
    }

    /**
     * Tripcode and email formatting.
     */
    /* @var HtmlElement $nameBlock */
    $nameBlock = span('', 'nameBlock' . $nameBlockExtra);
    $nameTrip = "";
    $name = span($post->getName(), 'name');
    if ($post->getTripcode() != '') {
      $nameTrip = $name . ' ' . span($post->getTripcode(), 'postertrip');
    } else {
      $nameTrip = (string)$name;
    }
    if ($post->getEmail() != '') {
      $nameBlock->append(a($nameTrip, 'mailto:' . $post->getEmail())->set('class', 'useremail'));
    } else {
      $nameBlock->append($nameTrip);
    }
    $nameBlock->append($cap);

    if ($post->getID() != "") {
      $idLink = a($post->getID(), "/{$post->getBoard()}/search/id/" . str_replace('/', '-', $post->getID()))
                ->set('title', 'View posts by this ID')
                ->set('class', 'hand posteruid postNum');
      $idSpan = span("(ID: $idLink)", 'posteruid postNum id_' . $post->getID());
      $nameBlock->append(' ' . $idSpan);
    }
    return $nameBlock;
  }


  public static function fixHTML(Post $p):string
  {
    if($p->getBoard()->isArchive()) {
      return self::transform4chanHtml($p->getComment(), $p->getBoard(), $p->getThreadId());
    } else {
      return self::transformHtml($p);
    }
  }

  private static function transformHtml(Post $p):string
  {
    return Yotsuba::parseBBCode($p);
  }

  /**
   * Fix links from 4chan for use in the archive.
   * Yes, I know I'm using regex to deal with HTML, but it works.
   *
   * @todo Use an actual HTML parser rather than regex
   * @param string $com the post comment (full HTML)
   * @param Board $board the board
   * @param int $thread the threadid
   * @return string the fixed HTML
   */
  private static function transform4chanHtml($com, $board, $thread):string {
    $search = array();
    $replace = array();
    //For links to /b/ threads
    $search[0] = '/<a href="(\d+)#p(\d+)" class="quotelink">/';
    $replace[0] = '<a href="$1#p$2" data-board="' . $board . '" data-thread="$1" data-post="$2" class="quotelink">';


    //new for OP quotes, thanks moot you asshole
    $search[1] = '~<a href="/' . $board . '/thread/' . $thread . '#p(\d+)" class="quotelink">~';
    $replace[1] = '<a href="' . $thread . '#p$1" data-board="' . $board . '" data-thread="' . $thread . '" data-post="$1" class="quotelink">';

    //hopefully dead links haven't changed
    $search[2] = '/<span class="deadlink">/';
    $replace[2] = '<span class="deadlink" data-board="' . $board . '">';

    //STOP CHANGING THINGS MOOT HOLY SHIT
    $search[3] = '/<a href="#p(\d+)" class="quotelink">/';
    $replace[3] = '<a href="#p$1" data-board="' . $board . '" data-thread="' . $thread . '" data-post="$1" class="quotelink">';

    //For links to other boards' threads
    $search[4] = '~<a href="/' . $board . '/(res|thread)/(\d+)#p(\d+)" class="quotelink">~';
    $replace[4] = '<a href="/' . $board . '/$1/$2#p$3" data-board="' . $board . '" data-thread="$2" data-post="$3" class="quotelink">';

    //For links to other boards' threads
    //$search[5] = '~<a href="/(\w+)/(res|thread)/(\d+)#p(\d+)" class="quotelink">~';
    //$replace[5] = '<a href="//boards.4chan.org/$1/$2/$3#p$4" class="quotelink">';

    $ret = preg_replace($search, $replace, $com);

    return $ret;
  }
}