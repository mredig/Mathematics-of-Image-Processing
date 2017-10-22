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
$matrix = array(-1,-0.5,0,-0.5,1,0.5,0,0.5,1);
filterImage($image1, $result, $matrix, 1, 0);

// send results to client
header('Content-Type: image/jpeg');
imagejpeg($result);
imagedestroy($result);
imagedestroy($image1);

function filterImage(&$imageInput, &$resultImage, $convolution, $divisor, $offset) {
	$dx = imagesx($imageInput);
	$dy = imagesy($imageInput);

	$convoTotal = count($convolution);
	$convoDimensions = sqrt($convoTotal);
	if ((intval($convoDimensions) != $convoDimensions) || ($convoDimensions % 2 == 0)) {
		print_r($convolution);
		die("improper matrix provided, see above.\n");
	}

	$sampleArray2D = array();

	// put all samples into a 2d (y,x) array before manipulation
	for ($y=0; $y < $dy; $y++) {
		$row = array();
		for ($x=0; $x < $dx; $x++) {
			$sampleX = $x;
			$sampleY = $y;

			$rgbColor = imagecolorat($imageInput, $sampleX, $sampleY);
			$row[] = $rgbColor;
			imagecolordeallocate($imageInput, $rgbColor);
		}
		$sampleArray2D[] = $row;
	}

	// run convolution
	$convolutionOffset = ($convoDimensions - 1) / 2;
	for ($y=0; $y < $dy; $y++) {
		for ($x=0; $x < $dx; $x++) {
			$rTotal = 0;
			$gTotal = 0;
			$bTotal = 0;
			for ($i=0; $i < $convoTotal; $i++) {
				$xVal = $x + ($i % $convoDimensions) - $convolutionOffset;
				$yVal = $y + intval($i / $convoDimensions) - $convolutionOffset;
				$thisSample = getPixelAtCoordinateWithDimensions($xVal, $yVal, $dx, $dy, $sampleArray2D);


				$r = ($thisSample >> 16) & 0xFF; //getting other color values
				$g = ($thisSample >> 8) & 0xFF;
				$b = $thisSample & 0xFF;

				$rTotal += $r * $convolution[$i];
				$gTotal += $g * $convolution[$i];
				$bTotal += $b * $convolution[$i];
			}

			$rTotal = abs($rTotal); // set to absolute value
			$gTotal = abs($gTotal);
			$bTotal = abs($bTotal);


			$rTotal /= $divisor; // apply divisor
			$gTotal /= $divisor;
			$bTotal /= $divisor;

			$rTotal += $offset; // apply offset
			$gTotal += $offset;
			$bTotal += $offset;

			$rTotal = max(min($rTotal, 255), 0); // make sure within accepted range
			$gTotal = max(min($gTotal, 255), 0);
			$bTotal = max(min($bTotal, 255), 0);



			//// forumla ends here!

			$theColor = imagecolorallocate($resultImage, $rTotal, $gTotal, $bTotal);
			imagesetpixel($resultImage, round($x),round($y), $theColor);
			imagecolordeallocate($resultImage, $theColor);
		}
	}

}

function getPixelAtCoordinateWithDimensions($x, $y, $dx, $dy, &$sampleArray2D) {

	$x = min(max($x, 0), $dx - 1);
	$y = min(max($y, 0), $dy - 1);

	return $sampleArray2D[$y][$x];
}


?>
