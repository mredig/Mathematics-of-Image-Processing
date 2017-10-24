<?php
// ini_set('display_errors', 1); // leave this off when in production - useful for debugging, but will show errors from loading config
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// load image
$image1 = imagecreatefromjpeg("lighthouse.jpg");
// $image1 = imagecreatefromjpeg("http://localhost/lighthouse.jpg");

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

			$sampleX = $x;
			$sampleY = $y;

			$rgbColor = imagecolorat($imageInput, $sampleX, $sampleY);
			$r = ($rgbColor >> 16) & 0xFF; //getting other color values
			$g = ($rgbColor >> 8) & 0xFF;
			$b = $rgbColor & 0xFF;
			imagecolordeallocate($imageInput, $rgbColor);

			$rOut = $r;
			$gOut = $g;
			$bOut = $b;
			//// forumla ends here!

			$theColor = imagecolorallocate($resultImage, $rOut, $gOut, $bOut);
			imagesetpixel($resultImage, round($x),round($y), $theColor);
			imagecolordeallocate($resultImage, $theColor);

		}
	}
}


?>
