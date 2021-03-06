<?php

namespace View;

/**
 * An element of HTML.
 *
 */
class HtmlElement
{
  /**
   * @var string The name of this tag
   */
  private $tag;

  /**
   * @var string The inner content of the element.
   */
  private $content;

  /**
   * @var array Array of HTML attributes.
   */
  private $attrs;

  public function __construct($tag, $content = "", $attrs = [])
  {
    $this->tag = $tag;
    $this->content = $content;
    $this->attrs = $attrs;
  }

  /**
   * Sets the tag name
   * @param string $tag
   * @return \HtmlElement
   */
  public function setTag($tag)
  {
    $this->tag = $tag;
    return $this;
  }

  /**
   * Replaces the content of this element.
   * @param string $content
   * @return \HtmlElement
   */
  public function setContent($content)
  {
    $this->content = $content;
    return $this;
  }

  /**
   * Adds to this tag's content.
   * @param string $content
   * @return \HtmlElement
   */
  public function append($content)
  {
    $this->content .= $content;
    return $this;
  }

  /**
   * Remove all attributes from this element
   * @return \HtmlElement
   */
  public function clearAttrs()
  {
    $this->attrs = [];
    return $this;
  }

  /**
   * Add or modify an attribute
   * @param string $name Name of the attribute
   * @param string $value Value for the attribute (or empty string for non-valued attributes)
   * @return \HtmlElement
   */
  public function set($name, $value = "")
  {
    $this->attrs[$name] = $value;
    return $this;
  }

  /**
   * Remove an attribute
   * @param string $name the attribute to remove
   * @return \HtmlElement
   */
  public function clear($name)
  {
    if (isset($this->attrs[$name])) {
      unset($this->attrs[$name]);
    }
    return $this;
  }

  public function __toString()
  {
    $ret = "<$this->tag";
    foreach ($this->attrs as $name => $value) {
      if ($value == "") {
        $ret .= ' ' . $name;
      } else {
        $ret .= ' ' . $name . '="' . $value . '"';
      }
    }
    $ret .= '>' . $this->content . '</' . $this->tag . '>';
    return $ret;
  }
}