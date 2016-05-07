<?php

namespace ImageBoard;

use Exception;
use ImageBoard\Board;
use ImageBoard\Post;
use Iterator;
use Model\Model;
use View\PostRenderer;
use type;

class Thread implements Iterator
{

  static function fromArray(Board $board, array $details)
  {
    return new Thread($details['threadid'], $board, $details['sticky'], $details['closed'], (isset($details['tag']) ? $details['tag'] :
        isset($details['type']) ? $details['type'] : null), $details['active'], $details['replies'], $details['images']);
  }

  /**
   * @var int The OP's ID; the value of resto
   */
  private $threadId;

  /**
   * @var array posts
   */
  private $posts;

  /**
   * @var Board
   */
  private $board;

  /**
   * @var bool
   */
  private $sticky;

  /**
   * @var bool
   */
  private $closed;

  /**
   * @var int Number of image posts, according to 4chan
   */
  private $chan_images;

  /**
   * @var int Number of posts, according to 4chan
   */
  private $chan_posts;

  /**
   * @var int Number of images, actually in archive.
   */
  private $num_images;

  /**
   * @var int Actual number of posts marked as deleted in archive.
   */
  private $num_deleted;

  /**
   * @var int Actual number of posts in archive.
   */
  private $num_posts;

  /**
   *
   * @var string The tag (e.g. for /f/, or the Tripfag-Finder mark for /b/)
   */
  private $tag;

  /** @var int Current iterator index */
  private $index;

  /**
   * Thread constructor. (obvious, change this)
   *
   * @param string|int $thrdId the thread ID/res number
   * @param Board $board the thread's board
   * @param bool $sticky
   * @param bool $closed
   */
  function __construct(int $thrdId, Board $board, bool $sticky = false, bool $closed = false, $tag = null, $active = null, $chanPosts = null, $chanImages = null)
  {
    if (!is_numeric($thrdId))
      throw new Exception("Thread ID is invalid.");
    $this->threadId = $thrdId;
    $this->posts = array();
    $this->board = $board;
    $this->sticky = $sticky;
    $this->closed = $closed;
    $this->chan_posts = $chanPosts;
    $this->chan_images = $chanImages;
    $this->tag = $tag;
    $this->active = $active;
  }

  // Getters and setters //
  function getBoard(): Board
  {
    return $this->board;
  }

  function getThreadId(): int
  {
    return $this->threadId;
  }

  /**
   *
   * @return string The thread's tag.
   */
  function getTag()
  {
    return $this->tag;
  }

  function getDeleted()
  {
    return $this->num_deleted;
  }

  function getChanPosts()
  {
    return $this->chan_posts;
  }

  function getPosts()
  {
    return $this->num_posts;
  }

  function getChanImages()
  {
    return $this->chan_images;
  }

  function getImages()
  {
    return $this->num_images;
  }

  /**
   * @param int $n
   * @return Post The post at the index, or null
   */
  function getPost($n)
  {
    if ($n < count($this->posts))
      return $this->posts[$n];
    else
      return null;
  }

  function isActive()
  {
    return $this->active;
  }

  function asArray(): array
  {
    return [
        "board" => $this->board->getName(),
        "num" => $this->threadId,
        "replies" => $this->chan_posts,
        "images" => $this->chan_images
    ];
  }

  /**
   * Loads the entire thread from the DB.
   * Only works if no posts have been loaded yet.
   * @return \Thread reference to self
   */
  function loadAll()
  {
    if (count($this->posts) == 0) {
      $this->num_deleted = 0;
      $this->num_posts = 0;
      $this->num_images = 0;
      $tmp = Model::get()->getAllPosts($this);
      foreach ($tmp as $post) {
        $this->addPost($post);
        $this->num_posts++;
        if ($post->isDeleted()) {
          $this->num_deleted++;
        }
        if ($post->hasImage()) {
          $this->num_images++;
        }
      }
    } else {
      throw new Exception("Cannot load all posts if thread already contains posts.");
    }
    return $this;
  }

  /**
   * Loads only the OP. If OP is already loaded, does nothing.
   * @return \Thread reference to self
   */
  function loadOP()
  {
    if (count($this->posts) == 0) {
      $op = Model::get()->getPost($this->board, $this->threadId);
      $this->addPost($op);
      $this->tag = ($op->getTag() ? $op->getTag() : null);
      $this->num_posts = 1;
      $this->num_images = 1;
    }
    return $this;
  }

  /**
   * Loads the last (n) posts from the DB
   * @param type $n
   * @return \Thread reference to self
   */
  function loadLastN($n)
  {
    try {
      $posts = Model::get()->getLastNReplies($this, $n);
      foreach ($posts as $p) {
        $this->addPost($p);
      }
    } catch (Exception $e) {
    }
    return $this;
  }

  /**
   * addPost
   *
   * @param Post $post post to be added to the thread's array of posts.
   */
  function addPost($post)
  {
    $post->setBoard($this->board);
    $this->posts[] = $post;
    $this->parseQuotes($post->com, $post->no);
  }

  /**
   * <code>Thread::parseQuotes</code> searches for inter-post links and adds backlinks to the respective posts.
   *
   * @todo Use getters and setters rather than public attributes.
   * @todo Put this functionality into the b-stats native extension to save server resources.
   * @todo Better inline comments in this function.
   * @todo Show if backlinks are from (Dead) posts
   * @param string $com the post text to be searched
   * @param string|int $no the id of the post to be searched
   */
  function parseQuotes($com, $no)
  {
    $matches = array();
    $search = '~([a-z]+)link">&gt;&gt;(\d+)</~';
    preg_match_all($search, $com, $matches);
    for ($i = 0; $i < count($matches[1]); $i++) {
      $postno = $matches[2][$i];
      for ($j = 0; $j < count($this->posts); $j++) {
        $p = $this->posts[$j];
        if ($p->no == $postno) {
          if (!in_array($no, $p->backlinks)) {
            $this->posts[$j]->backlinks[] = $no;
          }
          break;
        }
      }
    }
  }

  /**
   * <code>Thread::displayThread</code> displays all the posts in a thread in 4chan style
   *
   * @return string Thread in HTML form
   */
  function displayThread()
  {
    $ret = "<div class='thread' id='t" . $this->threadId . "'>";
    $op = array_shift($this->posts);
    $ret .= PostRenderer::renderPost($op, PostRenderer::DISPLAY_OP, $this->sticky, $this->closed);
    foreach ($this->posts as $p) {
      $ret .= "<div class='sideArrows'>&gt;&gt;</div>" . PostRenderer::renderPost($p);
    }
    $ret .= "</div>";
    return $ret;
  }

  /*
   * Iterator functions
   */

  function rewind()
  {
    $this->index = 0;
  }

  function valid()
  {
    return ($this->index < count($this->posts));
  }

  function key()
  {
    return $this->index;
  }

  /**
   * @return Post
   */
  function current()
  {
    return $this->posts[$this->index];
  }

  function next()
  {
    $this->index++;
  }

}
