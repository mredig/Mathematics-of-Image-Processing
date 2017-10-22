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
filterImage($image1, $result, 20);

// send results to client
header('Content-Type: image/jpeg');
imagejpeg($result);
imagedestroy($result);
imagedestroy($image1);



function filterImage(&$imageInput, &$resultImage, $blurAmount = 5) {
	$dx = imagesx($imageInput);
	$dy = imagesy($imageInput);


	// load entire image into fast arrays in memory
	$rSource = array();
	$gSource = array();
	$bSource = array();
	for ($yValue=0; $yValue < $dy; $yValue++) {
		for ($xValue=0; $xValue < $dx; $xValue++) {

			$rgbColor = imagecolorat($imageInput, $xValue, $yValue);
			$r = ($rgbColor >> 16) & 0xFF; //getting other color values
			$g = ($rgbColor >> 8) & 0xFF;
			$b = $rgbColor & 0xFF;
			imagecolordeallocate($imageInput, $rgbColor);

			$rSource[] = $r;
			$gSource[] = $g;
			$bSource[] = $b;
		}
	}


	$rTarget = array();
	$gTarget = array();
	$bTarget = array();

	gaussBlur($rSource, $rTarget, $dx, $dy, $blurAmount);
	gaussBlur($gSource, $gTarget, $dx, $dy, $blurAmount);
	gaussBlur($bSource, $bTarget, $dx, $dy, $blurAmount);


	// for ($i=0; $i < count($rTarget); $i++) {
	// 	print "source: $rSource[$i] target: $rTarget[$i]\n";
	// }

	for ($i=0; $i < count($rTarget); $i++) {
		$x = $i % $dx;
		$y = intval($i / $dx);

		$theColor = imagecolorallocate($resultImage, $rTarget[$i], $gTarget[$i], $bTarget[$i]);
		imagesetpixel($resultImage, round($x),round($y), $theColor);
		imagecolordeallocate($resultImage, $theColor);
	}



}

// sourced from http://blog.ivank.net/fastest-gaussian-blur.html

function boxesForGauss($blurSigma, $boxCount) { //this function is magic and determines the approximate values needed to run a box blur $boxCount times to achieve a gaussianBlur equivalent at $blurSigma radius - don't ask about the math
	$wIdeal = sqrt(((12 * $blurSigma * $blurSigma) / $boxCount) + 1);
	$wl = intval(floor($wIdeal));

	if ($wl % 2 == 0) $wl--;
	$wu = $wl + 2;

	$mIdeal = (12 * $blurSigma * $blurSigma  -  $boxCount * $wl * $wl  -  4 * $boxCount * $wl  -  3 * $boxCount) / (-4 * $wl  -  4);
	$m = intval(round($mIdeal));

	$sizes = array();
	for ($i=0; $i < $boxCount; $i++) {
		$sizes[] = $i < $m ? $wl:$wu;
	}

	return $sizes;
}

function gaussBlur (&$sourceChannelArray, &$targetChannelArray, $width, $height, $r) {
	$boxRadii = boxesForGauss($r, 3); //derive box blur valus from desired radius input, then run multiple, smaller box blurs
	boxBlur($sourceChannelArray, $targetChannelArray, $width, $height, ($boxRadii[0]-1)/2);
	boxBlur($targetChannelArray, $sourceChannelArray, $width, $height, ($boxRadii[1]-1)/2);
	boxBlur($sourceChannelArray, $targetChannelArray, $width, $height, ($boxRadii[2]-1)/2);
}

function boxBlur(&$sourceChannelArray, &$targetChannelArray, $width, $height, $boxRadius) {
	for ($i=0; $i < count($sourceChannelArray); $i++) {
		$targetChannelArray[$i] = $sourceChannelArray[$i];
	}
	boxBlurH($targetChannelArray, $sourceChannelArray, $width, $height, $boxRadius);
	boxBlurV($sourceChannelArray, $targetChannelArray, $width, $height, $boxRadius);
}

function boxBlurH(&$sourceChannelArray, &$targetChannelArray, $width, $height, $boxRadius) {

	$accumlatorAverager = 1 / ($boxRadius + $boxRadius + 1); // radius range on either side of a pixel + the pixel itself
	for ($i=0; $i < $height; $i++) {
		$ti = $i * $width; //pixel index; will traverse the width of the image for each loop of the parent "for loop"
		$li = $ti; // trailing pixel index
		$ri = $ti + $boxRadius; //pixel index of the furthest reach of the radius

		$firstValue = $sourceChannelArray[$ti]; // first pixel value of the row
		$lastValue = $sourceChannelArray[$ti + $width - 1]; // last pixel value in the row
		$val = ($boxRadius + 1) * $firstValue; // create a "value accumulator" - we will be calculating the average of pixels surrounding each one - is faster to add newest value, remove oldest, and then average. This initial value is for pixels outside image bounds

		//for length of radius, accumulate the total value of all pixels from current pixel index and record it into the target channel first pixel
		for ($j = 0; $j < $boxRadius; $j++) {
			$val += $sourceChannelArray[$ti + $j];
		}

		// for the next $boxRadius pixels in the row, record pixel value of average of all pixels within the radius and save average into target channel
		for ($j = 0; $j <= $boxRadius; $j++) {
			$val += $sourceChannelArray[$ri++] - $firstValue;
			$targetChannelArray[$ti++] = round($val * $accumlatorAverager);
		}

		// now that we've completely removed the overflow pixels from the value accumulator, continue on, adding new values, removing old ones, and averaging the acculated value
		for ($j = $boxRadius + 1; $j < $width - $boxRadius; $j++) {
			$val += $sourceChannelArray[$ri++] - $sourceChannelArray[$li++];
			$targetChannelArray[$ti++] = round($val * $accumlatorAverager);
		}

		// finish off the row of pixels, duplicating the edge pixel instead of going out of image bounds
		for ($j= $width - $boxRadius; $j < $width; $j++) {
			$val += $lastValue - $sourceChannelArray[$li++];
			$targetChannelArray[$ti++] = round($val * $accumlatorAverager);
		}
	}


}

//// this does the same thing as boxBlurH, but vertically
function boxBlurV(&$sourceChannelArray, &$targetChannelArray, $width, $height, $boxRadius) {
	$accumlatorAverager = 1 / ($boxRadius + $boxRadius + 1);
	for ($i=0; $i < $width; $i++) {
		$ti = $i;
		$li = $ti;
		$ri = $ti + $boxRadius * $width;

		$firstValue = $sourceChannelArray[$ti];
		$lastValue = $sourceChannelArray[$ti + $width * ($height - 1)];
		$val = ($boxRadius + 1) * $firstValue;

		for ($j=0; $j < $boxRadius; $j++) {
			$val += $sourceChannelArray[$ti + $j * $width];
		}

		for ($j=0; $j <= $boxRadius; $j++) {
			$val += $sourceChannelArray[$ri] - $firstValue;
			$targetChannelArray[$ti] = round($val * $accumlatorAverager);
			$ri += $width;
			$ti += $width;
		}

		for ($j=$boxRadius + 1; $j < $height - $boxRadius; $j++) {
			$val += $sourceChannelArray[$ri] - $sourceChannelArray[$li];
			$targetChannelArray[$ti] = round($val * $accumlatorAverager);
			$li += $width;
			$ri += $width;
			$ti += $width;
		}

		for ($j=$height - $boxRadius; $j < $height; $j++) {
			$val += $lastValue - $sourceChannelArray[$li];
			$targetChannelArray[$ti] = round($val * $accumlatorAverager);
			$li += $width;
			$ti += $width;
		}
	}
}

?>
