<?php
// ini_set('display_errors', 1); // leave this off when in production - useful for debugging, but will show errors from loading config
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// load image
$image1 = imagecreatefromjpeg("lighthouse.jpg");

// get image dimensions for creating the final output canvas
$dx = imagesx($image1);
$dy = imagesy($image1);

$result = imagecreatetruecolor($dx, $dy);

// run the filter
filterImage($image1, $result);

// send results to client
header('Content-Type: image/jpeg');
imagejpeg($result);
imagedestroy($result);
imagedestroy($image1);

function filterImage(&$imageInput, &$resultImage) {
	$dx = imagesx($imageInput);
	$dy = imagesy($imageInput);

	for ($y=0; $y < $dy; $y++) {
		for ($x=0; $x < $dx; $x++) {
			//// formula goes here!

			$offsetX = -90;
			$offsetY = 60;

			$sampleX = $x + $offsetX;
			$sampleY = $y + $offsetY;

			while ($sampleX >= $dx) {
				$sampleX -= $dx;
			}

			while ($sampleX < 0) {
				$sampleX += $dx;
			}

			while ($sampleY >= $dy) {
				$sampleY -= $dy;
			}

			while ($sampleY < 0) {
				$sampleY += $dy;
			}

			$rgbColor = imagecolorat($imageInput, $sampleX, $sampleY);
			$r = ($rgbColor >> 16) & 0xFF; //getting other color values
			$g = ($rgbColor >> 8) & 0xFF;
			$b = $rgbColor & 0xFF;
			imagecolordeallocate($imageInput, $rgbColor);


			//// forumla ends here!

			$theColor = imagecolorallocate($resultImage, $r, $g, $b);
			imagesetpixel($resultImage, round($x),round($y), $theColor);
			imagecolordeallocate($resultImage, $theColor);
		}
	}
}


?>
