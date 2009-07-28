<?php

	require 'CImage.php';

	// Give original file to the class constructor
	$img = new CImage( 'original.jpg' );

	// Define thumbnail file
	$img->setThumbnail( '/tmp/scaled.png' );

	// Define thumbnail format. If this is not given, then the
	// output file will be in JPEG-format.
	$img->setFormat( 'png' );

	// If there is already file what is set in setThumbnail, then
	// this will define that we must overwrite file. Otherwise
	// if file exists, it will throw Exception
	$img->setOverwrite( true );

	// And now, resize to 500x200, but keep aspect ratio.
	// If you want excatly 500x200, then use this without third parameter.
	$img->resize( '500', '200', 'FIT' );

?>
