<?php

namespace View\Pages;

use Site\Site;
use View\IPage;

class Captcha implements IPage
{

  public function display():string
  {
    $img = imagecreatetruecolor(96, 48);
    $image_text = empty($_SESSION['captcha']) ? 'error' : $_SESSION['captcha'];
    srand(time()/60);
    $red = rand(0, 100);
    $green = rand(0, 100);
    $blue = rand(0, 100);
    $text_color = imagecolorallocate($img, 255 - $red, 255 - $green, 255 - $blue);
    $strlen = strlen($image_text);
    $char_array = str_split($image_text);
    for ($i = 0; ($i) < $strlen; $i++) {
      imagettftext($img, rand(13, 16), rand(-20, 20), 5 + 15 * $i, rand(15, 40), $text_color, Site::getPath()."/cfg/DroidSansMono.ttf", $char_array[$i]);
    }
    header("Content-type:image/jpeg");
    header("Content-Disposition:inline ; filename=secure.jpg");
    imagejpeg($img, null, 5);
    exit;
  }
}
