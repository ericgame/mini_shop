<?php
session_start();
header("Content-type: image/png");
$im              = imagecreatetruecolor(36, 20) or die("無法建立圖片！");
$text_color      = imagecolorallocate($im, 255, 255, 255);
$num             = rand(000, 999);
$_SESSION['key'] = $num;
imagestring($im, 5, 5, 2, $num, $text_color);
imagepng($im);
imagedestroy($im);
