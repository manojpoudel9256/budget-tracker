<?php
// Generate a 180x180 PNG icon dynamically for Apple Touch Icon
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

$size = 180;
$img = imagecreatetruecolor($size, $size);

// Enable anti-aliasing
imageantialias($img, true);

// Gradient background (indigo to purple)
for ($y = 0; $y < $size; $y++) {
    $ratio = $y / $size;
    $r = (int) (99 + (139 - 99) * $ratio);
    $g = (int) (102 + (92 - 102) * $ratio);
    $b = (int) (241 + (246 - 241) * $ratio);
    $color = imagecolorallocate($img, $r, $g, $b);
    imageline($img, 0, $y, $size, $y, $color);
}

// Draw wallet icon (white)
$white = imagecolorallocate($img, 255, 255, 255);
$whiteAlpha = imagecolorallocatealpha($img, 255, 255, 255, 60);

// Wallet body - rectangle with rounded corners
$bx1 = 45;
$by1 = 60;
$bx2 = 135;
$by2 = 125;
imagesetthickness($img, 4);

// Body outline
imagerectangle($img, $bx1, $by1, $bx2, $by2, $white);

// Wallet flap line
imageline($img, 45, 60, 45, 48, $white);
imageline($img, 45, 48, 110, 48, $white);
imageline($img, 110, 48, 135, 60, $white);

// Card slot circle
imagefilledellipse($img, 122, 93, 14, 14, $white);

// Yen symbol
$fontSize = 5; // GD built-in font size
$text = 'Y';
$textWidth = imagefontwidth($fontSize) * strlen($text);
$textX = ($size - $textWidth) / 2;
imagestring($img, $fontSize, $textX, 140, $text, $whiteAlpha);

imagepng($img);
imagedestroy($img);
