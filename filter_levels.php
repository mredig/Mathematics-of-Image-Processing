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

function filterImage(&$imageInput, &$resultImage, $blackPoint = 50, $whitePoint = 200) {
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

			// blackPoint
			$r -= $blackPoint;
			$r *= 255 / (255 - $blackPoint);
			$g -= $blackPoint;
			$g *= 255 / (255 - $blackPoint);
			$b -= $blackPoint;
			$b *= 255 / (255 - $blackPoint);

			//whitePoint
			$r *= 255 / $whitePoint;
			$g *= 255 / $whitePoint;
			$b *= 255 / $whitePoint;



			$rOut = round(max(min($r, 255), 0));// make sure values are integral and do not exceed 255 or go below 0
			$gOut = round(max(min($g, 255), 0));
			$bOut = round(max(min($b, 255), 0));
			//// forumla ends here!

			$theColor = imagecolorallocate($resultImage, $rOut, $gOut, $bOut);
			imagesetpixel($resultImage, round($x),round($y), $theColor);
			imagecolordeallocate($resultImage, $theColor);

		}
	}
}


?>
