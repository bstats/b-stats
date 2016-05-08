<?php
namespace Model;


class FileInfo
{
  /**
   * FileInfo constructor.
   */
  public function __construct()
  {
    $this->name = '';
    $this->ext = '';
    $this->w = 0;
    $this->h = 0;
    $this->size = 0;
    $this->hash = hex2bin('00000000000000000000000000000000');
  }


  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return FileInfo
   */
  public function setName($name)
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getExt()
  {
    return $this->ext;
  }

  /**
   * @param string $ext
   * @return FileInfo
   */
  public function setExt($ext)
  {
    $this->ext = $ext;
    return $this;
  }

  /**
   * @return int
   */
  public function getW()
  {
    return $this->w;
  }

  /**
   * @param int $w
   * @return FileInfo
   */
  public function setW($w)
  {
    $this->w = $w;
    return $this;
  }

  /**
   * @return int
   */
  public function getH()
  {
    return $this->h;
  }

  /**
   * @param int $h
   * @return FileInfo
   */
  public function setH($h)
  {
    $this->h = $h;
    return $this;
  }

  /**
   * @return int
   */
  public function getSize()
  {
    return $this->size;
  }

  /**
   * @param int $size
   * @return FileInfo
   */
  public function setSize($size)
  {
    $this->size = $size;
    return $this;
  }

  /**
   * @return string
   */
  public function getHash()
  {
    return $this->hash;
  }

  /**
   * @param string $hash
   * @return FileInfo
   */
  public function setHash($hash)
  {
    $this->hash = $hash;
    return $this;
  }
  /** @var  string the filename, without extension */
  private $name;
  /** @var  string the extension, including the '.' */
  private $ext;
  /** @var  int the width of the media */
  private $w;
  /** @var  int the height of the media */
  private $h;
  /** @var  int the size in bytes of the media */
  private $size;
  /** @var  string the binary md5 of the media */
  private $hash;

}