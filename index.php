<html>
<head>
	<title>crashterizer</title>
</head>
<body>
	<h1>crashterizer</h1>
	<form method="post" enctype="multipart/form-data">
		<input type="file" name="image">
		<input type="submit" value="Submit image">
		<br>jpg or png only, original image will be discarded after crashterizing.
		<br>
		<br><a href='r/'>view crashterized images</a>
		<br>
		<br>source on <a href='https://github.com/Xkeeper0/crashterizer'>github</a>
	</form>
<?php


	// If we have an image uploaded, it's time to do stuff. Stuff!
	if (isset($_FILES['image']) && !$_FILES['image']['error']) {

		// Get the extension. This is really bad but who cares --
		// ideally use a regex match or just split based on . and read the last element
		// if they try and be malicious it won't load as an image anyway, and will fail shortly after
		$ext	= substr($_FILES['image']['name'], -4);


		// Based on that extension, try loading it as that file type with gd
		switch ($ext) {
			case ".png":
				$im		= imagecreatefrompng($_FILES['image']['tmp_name']);
				break;

			case ".jpg":
			case "jpeg":
				$im		= imagecreatefromjpeg($_FILES['image']['tmp_name']);
				break;

			default:
				$im		= null;
				break;
		}

		// In the event the image actually loaded, time to mangle it
		if ($im !== null && $im !== false) {

			// Base CHR image, 128x128, ideally 4 colors but doesn't really matter atm
			$blimg	= imagecreatefrompng("chr.png");

			// Crush the input image into 1/8th the size
			$ni		= shrinkby8($im);

			// Delete the original image to free up memory
			imagedestroy($im);

			// Create a new image that's 8x as large as our crushed image
			// (Creating it based on the original image size could leave it not a multiple of 8)
			$sx		= imagesx($ni);
			$sy		= imagesy($ni);
			$fi		= imagecreatetruecolor($sx * 8, $sy * 8);

			// Loop through all pixels in the crushed image...
			for ($y = 0; $y < $sy; $y++) {
				for ($x = 0; $x < $sx; $x++) {
					// Get the brightness of the pixel using a simple calc
					$p	= round(samplepixel($ni, $x, $y) * 255);

					// Copy the respective block from our CHR image to the new image
					copyblock($fi, $blimg, $x, $y, $p);
				}
			}

			// Output to a file based on the current microtime
			$fn		= "r/". microtime(true) .".png";
			imagepng($fi, $fn);

			// Display! Wow, maybe print it out and hang it in a museum while you're at it.
			print "<img src='$fn' title='wow! modern art.'>";


		} elseif ($im === false) {
			// Someone tried to upload a bad image. Very naughty.
			print "Error handling that image, oh well. Sorry.";

		} elseif ($im === null) {
			// Uploaded something that wasn't an image at all! Read the instructions next time
			// Maybe I should just randomly generate an image for non-image files, that'd be funny
			print "Unhandled file type, sorry. Try PNG or JPG.";

		}

		// Clean up after ourselves.
		unlink($_FILES['image']['tmp_name']);

	}





	// Return a crushed version of an image to 1/8th of its size using resampling
	function shrinkby8($im) {
		$sx	= imagesx($im);
		$sy	= imagesy($im);

		$ni	= imagecreatetruecolor($sx / 8, $sy / 8);

		imagecopyresampled($ni, $im, 0, 0, 0, 0, imagesx($ni), imagesy($ni), $sx, $sy);

		return $ni;


	}

	// Get the generic "brightness" of a pixel by averaging its RGB components
	function samplepixel($im, $x, $y) {

		$rgb	= imagecolorat($im, $x, $y);
		$r		= ($rgb >> 16) & 0xFF;
		$g		= ($rgb >> 8) & 0xFF;
		$b		= $rgb & 0xFF;

		return ($r + $g + $b) / (255 * 3);

	}


	// Copy one 8x8 tile from a CHR image to a destination image by block ID
	function copyblock($im, $bi, $x, $y, $v) {

		$bx	= $v % 16;
		$by	= floor($v / 16);


		imagecopy($im, $bi, $x * 8, $y * 8, $bx * 8, $by * 8, 8, 8);


	}


?>
</body>
</html>
