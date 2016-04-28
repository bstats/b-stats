<?php

class_exists('HtmlElement');

/**
 * The Page class contains functions for creating and displaying a page with consistent style.
 */
class Page implements IPage {

  protected $startTime;
  protected $endTime;

  /**
   * @var string The page's body.
   */
  protected $body;

  /**
   * @var string The page's title.
   */
  protected $title;
  protected $addToHead;
  protected $header;
  protected $footer;
  protected $clearHeader;
  protected $clearFooter;

  /**
   * @var \HtmlElement The navbar.
   */
  protected $navbar;

  public function __construct($title, $body = "") {
    $this->startTime = microtime(true);
    $this->title = $title;
    $this->body = $body;
    $this->user = Site::getUser();

    $this->addToHead = "";
    $this->clearHeader = false;
    $this->clearFooter = false;
    $this->initNavbar();
  }

  private function initNavbar() {
    $this->navbar = div('', 'navbar');
    foreach (Config::getCfg('navlinks') as $name => $link) {
      $this->navbar->append(
              a(span("&nbsp;$name&nbsp;", 'navelement'), $link));
    }
    $stylelist = el('ul');
    foreach (Config::getCfg("styles") as $name => $css) {
      $stylelist->append(
              el('li', a("&nbsp;$name&nbsp;","javascript:")
                      ->set("onclick","StyleSwitcher.switchTo('$name')")
                      ->set("class",'navelement')->set("title",$name)));
    }
    $this->navbar->append(
            el('ul', el('li', '[Page Style]' . $stylelist, ['class' => 'navelement']), ['class' => 'stylemenu']));
  }

  /**
   * Sets the text in the <code>&lt;title&gt;</code> tags.
   * @param string $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return string The page's title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * For pages that take a while to load.
   * @return string Page's header 
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * Add code to the <code>&lt;head%gt;</code> section of the code
   * @param string $html HTML code to add to the <code>&lt;head%gt;</code> section
   */
  public function addToHead($html) {
    $this->addToHead .= $html;
  }

  /**
   * For pages that take a while to load.
   * @return string Page's footer 
   */
  public function getFooter() {
    return $this->footer;
  }

  /**
   * Returns the body's HTML code.
   * Only includes what's within the
   * <code>&lt;div class='content'%gt;&lt;/div&gt;</code> tags, not including
   * the header.
   * 
   * @return string The body of the page
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Replaces the page's body text.
   * @param string $html The html code to replace 
   */
  public function setBody($html) {
    $this->body = $html;
  }

  /**
   * Adds text to the body. (inside <code>&lt;div class='content'%gt;&lt;/div&gt;</code>)
   * @param string $html HTML to add to the body
   */
  public function appendToBody($html) {
    $this->body .= $html;
  }

  /**
   * @return double Current page execution time
   */
  function getElapsedTime() {
    return round(microtime(true) - $this->startTime, 4);
  }

  function clearHead() {
    $this->clearHeader = true;
  }

  function clearFoot() {
    $this->clearFooter = true;
  }

  /**
   * @return string The navbar.
   */
  protected function renderNavBar() {
    return (string) $this->navbar;
  }

  protected function renderHeader() {
    $styles = "";
    foreach(Config::getCfg('styles')[$this->user->getTheme()] as $css) {
      $styles .= "<link rel='stylesheet' type='text/css' href='$css' name='theme'>";
    }
    $ga = Config::getCfg('site')['ga_id'];
    $this->header = Site::parseHtmlFragment('pagehead.html', [
              '<!-- styles -->', '<!-- pageTitle -->',
              '<!-- additionalHeaders -->', '<!-- navbar -->','<!-- ga -->'], [$styles, $this->title,
              $this->addToHead, $this->renderNavBar(), Site::parseHtmlFragment("ga.html",['__ID__'],[$ga])]);
    return $this->header;
  }

  protected function renderFooter() {
    if (!$this->clearFooter) {
      $this->footer = Site::parseHtmlFragment("pagefoot.html", ["<!--copyright-->"], [file_get_contents("htmls/copyright.html")]);
    } else {
      $this->footer = file_get_contents("htmls/pagefoot.html");
    }
    return $this->footer;
  }

  /**
   * Renders and returns the page.
   * @return string the page's entire HTML
   */
  public function display():string {
    $hdr = $this->renderHeader();
    $footer = $this->renderFooter();
    $this->endTime = microtime(true);
    $time = el('p', 
            "page took " . round($this->endTime - $this->startTime, 4) .
            " seconds to execute",
            ['class'=>'pageTime']);
    return $hdr . $this->body . $time . $footer;
  }
}