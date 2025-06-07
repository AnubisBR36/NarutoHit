<?php
$codigo=base64_decode($_GET['cod']);
$x=rand(30,80);
$y=rand(10,30);
$gif = 'fundo_captcha.gif';
$img = imagecreatefromgif($gif); 
if($img){
header("Content-Type: image/png");
$corfonte=imagecolorallocate($img,20,20,20);
$font=imageloadfont('proggysquare.gdf');
imagestring($img,2,$x,$y,$codigo,$corfonte);
imagepng($img); 
imagedestroy($img);
} 
?>