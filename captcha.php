<?php
session_start();
$img=  imagecreatetruecolor(96, 48);
$image_text = empty($_SESSION['captcha']) ? 'error' : $_SESSION['captcha'];
srand($image_text);
$red=rand(0,100);
$green=rand(0,100);
$blue=rand(0,100);
$text_color=imagecolorallocate($img,255-$red,255-$green,255-$blue);
$strlen = strlen($image_text);
$char_array=str_split($image_text);
for($i=0;($i)<$strlen;$i++){
        imagettftext($img, rand(13,16), rand(-20,20), 5+15*$i, rand(15,40), $text_color,"/usr/share/fonts/truetype/droid/DroidSansMono.ttf", $char_array[$i]);
}
header("Content-type:image/jpeg");
header("Content-Disposition:inline ; filename=secure.jpg");
imagejpeg($img,null,5);