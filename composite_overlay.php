<?php
// ini_set('display_errors', 1); // leave this off when in production - useful for debugging, but will show errors from loading config
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// load image
$image1 = imagecreatefromjpeg("cat.jpg");
$image2 = imagecreatefromjpeg("overlayA.jpg");
// $image1 = imagecreatefromjpeg("http://localhost/lighthouse.jpg");

// get image dimensions for creating the final output canvas
$dx = imagesx($image1);
$dy = imagesy($image1);


// run the filter
compositeImages($image1, $image2); //we are assuming image1 and image2 are the same size

// send results to client
header('Content-Type: image/jpeg');
imagejpeg($image1);
imagedestroy($image2);
imagedestroy($image1);

function compositeImages(&$imageInput1, &$imageInput2) {
	$dx = imagesx($imageInput1);
	$dy = imagesy($imageInput1);

	for ($y=0; $y < $dy; $y++) {
		for ($x=0; $x < $dx; $x++) {

			$sampleX = $x;
			$sampleY = $y;

			$rgbColor = imagecolorat($imageInput1, $sampleX, $sampleY);
			$r1 = ($rgbColor >> 16) & 0xFF; //getting other color values
			$g1 = ($rgbColor >> 8) & 0xFF;
			$b1 = $rgbColor & 0xFF;
			imagecolordeallocate($imageInput1, $rgbColor);

			$rgbColor = imagecolorat($imageInput2, $sampleX, $sampleY);
			$r2 = ($rgbColor >> 16) & 0xFF; //getting other color values
			$g2 = ($rgbColor >> 8) & 0xFF;
			$b2 = $rgbColor & 0xFF;
			imagecolordeallocate($imageInput2, $rgbColor);

			//// formula goes here!


			$rOut = overlayAlg($r1, $r2);
			$gOut = overlayAlg($g1, $g2);
			$bOut = overlayAlg($b1, $b2);

			//// forumla ends here!

			$theColor = imagecolorallocate($imageInput1, $rOut, $gOut, $bOut);
			imagesetpixel($imageInput1, round($x),round($y), $theColor);
			imagecolordeallocate($imageInput1, $theColor);

		}
	}
}

function overlayAlg($valueA, $valueB) {
	if ($valueA < 127) {
		//// broken down
		// $newValue = $valueA * $valueB * 2;
		// $newValue /= 255;
		// $newValue = min(255, $newValue);
		// return $newValue;
		//// one line
		return min($valueA * $valueB * 2 / 255, 255);
	} else {
		//// broken down
		// $invertA = 255 - $valueA;
		// $invertB = 255 - $valueB;
		// $newValue = $invertA * $invertB * 2;
		// $newValue /= 255;
		// $newValue = 255 - $newValue;
		// $newValue = max(0, $newValue);
		// return $newValue;
		//// one line
		return max(255 - (2 * (255 - $valueA) * (255 - $valueB) / 255), 0);
	}
}


?>
