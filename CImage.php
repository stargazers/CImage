<?php

	/* 
	CImage - Image handling class for PHP
    Copyright (C) 2009 Aleksi Räsänen <aleksi.rasanen@runosydan.net>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/

	// *********************************************
	//	CImage
	/*!
		@brief Class for image resizings

		@author Aleksi Räsänen 2009
				aleksi.rasanen@runosydan.net
	*/
	// *********************************************
	class CImage
	{
		//! Fullsized image
		private $image;

		//! Thumbnail image file
		private $thumbnail;

		//! Thumbnail output filetype
		private $format;

		//! Thumbnail width and height
		private $width, $height;

		//! File extension
		private $extension;

		//! Overwrite thumbnail images if exists?
		private $overwrite;

		//! Quality. This is used when creating PNG or JPG
		private $quality;

		// *********************************************
		//	__construct
		/*!
			@brief Class constructor. Sets private $image
				value if imagefile is found
		
			@param $image Imagefile to use
		*/
		// *********************************************
		public function __construct( $image )
		{
			// By default we do not want to overwrite thumbnail files!
			$this->overwrite = false;

			// By default we want 100% quality
			$this->quality = 100;

			// By default image output format is JPG.
			$this->format == 'JPG';

			// If image is not found, just throw Exception.
			if(! file_exists( $image ) )
				throw new Exception( 'Image ' . $image . ' not found!' );

			// Fullsized image
			$this->image = $image;

			// Check image filetype. This will be stored in 
			// class variable $this->extension
			$this->getFiletype();
		}

		// *********************************************
		//	getFiletype
		/*!
			@brief Get filetype. This should be png,
				jpg or other image format. If it is NOT
				an image format, we will throw exception.
				NOTE! This function uses only detection
				by file extension. 
		*/
		// *********************************************
		private function getFiletype()
		{
			// Get position of last .
			$pos = strrpos( $this->image, '.' ) +1;

			// Read the part after last dot
			$ext = substr( $this->image, $pos );
			$ext = strtoupper( $ext );

			switch( $ext )
			{
				// List here supported formats
				case 'JPG':
				case 'PNG':
				case 'GIF':
					$this->extension = $ext;
					break;

				default:
					throw new Exception( 'Unsupported file format!' );
					break;
			}
		}

		// *********************************************
		//	setThumbnail
		/*!
			@brief Set name for thumbnailfile
		
			@param $file Thumbnailfile
		*/
		// *********************************************
		public function setThumbnail( $file )
		{
			$this->thumbnail = $file;
		}

		// *********************************************
		//	checkOverwrite
		/*!
			@brief Check if thumbnail file already exists.
				If file exists, check if it can be overwritten.
		*/
		// *********************************************
		private function checkOverwrite()
		{
			// If overwriting of files is disabled (and it is by default!),
			// then we need to check that we are not trying to overwrite
			// any already existing files in system.
			if(! $this->overwrite )
			{
				// Check if file exists with name $file.
				// If so, then throw exception.
				if( file_exists( $this->thumbnail ) )
				{
					throw new Exception( 'File ' . $file 
						. ' already exists!' );
				}
			}
		}

		// *********************************************
		//	resize
		/*!
			@brief Resize image
		
			@param $width Thumbnail width
		
			@param $height Thumbnail height
		
			@param $type='' Resize type.
		*/
		// *********************************************
		public function resize( $width, $height, $type='' )
		{
			// Division by zero is not supported by this program ;)
			if( $width == 0 || $height == 0 )
			{
				throw new Exception( 'Image width and height must be '
					. 'greater than zero!' );
			}

			// Check if thumbnail file already exists and if so,
			// can we just overwrite it
			$this->checkOverwrite();

			// Load image to memory. Use correct function depending
			// on original filetype.
			switch( $this->extension )
			{
				case 'JPG':
					$original = imagecreatefromjpeg( $this->image );
					break;

				case 'PNG':
					$original = imagecreatefrompng( $this->image );
					break;

				default:
					throw new Exception( 'Unsupported filetype!' );
					break;
			}

			// Read image width and height from original image
			list( $original_width, $original_height ) = getimagesize(
				$this->image );

			// Now, detect scale type and calculate size.
			// Note! If user has given other value than some of these,
			// then we do NOT calculate any size calculations. Just keep
			// those values what user has given.

			// Keep original width, scale only height
			if( $type == 'KEEP_WIDTH' )
			{
				$width = $original_width;
				$height = $height;
			}

			// Keep original height, scale only width
			else if( $type == 'KEEP_HEIGHT' )
			{
				$height = $original_height;
				$width = $width;
			}

			// Resize to fit in dimensions. This will keep aspect ratio.
			// This will scale image to take as much space as possible.
			else if( $type == 'FIT' )
			{
				if( $width >= $height )
				{
					$tmp = $original_width / $width;
					$width = $original_width / $tmp;
					$height = $original_height / $tmp;
				}
				else
				{
					$tmp = $original_height / $height;
					$height = $original_height / $tmp;
					$width = $original_width / $tmp;
				}
			}

			// Create empty scaled version
			$thumbnail = imagecreatetruecolor( $width, $height );

			// Resize image to correct dimensions
			imagecopyresampled( $thumbnail, $original, 0, 0, 0, 0, 
				$width, $height, $original_width, $original_height );

			// Be sure that quality is set properly
			$this->correctQuality();

			// Save resized image to thumbnailfile
			if( $this->format == 'JPG' )
				imagejpeg( $thumbnail, $this->thumbnail, $this->quality );
			else if( $this->format == 'PNG' )
				imagepng( $thumbnail, $this->thumbnail, $this->quality );
			else if( $this->format == 'GIF' )
				imagegif( $thumbnail, $this->thumbnail );
			else
				imagejpeg( $thumbnail, $this->thumbnail, $this->quality );

			// Free memory
			imagedestroy( $original );
		}

		// *********************************************
		//	setOverwrite
		/*!
			@brief Sets if thumbnail images will overwrite
				already existing thumbnails.
		
			@param $val True of false.
		*/
		// *********************************************
		public function setOverwrite( $val )
		{
			$this->overwrite = $val;
		}

		// *********************************************
		//	correctQuality
		/*!
			@brief Check if quality settings are correct
				for selected output type, and if not,
				fix them.
		
		*/
		// *********************************************
		private function correctQuality()
		{
			// Image compression cannot be under zero
			if( $this->quality < 0 )
				$this->quality = 0;

			// Quality settings depends on file format.
			// If we have PNG, quality cannot be greater than 9.
			// If we have JPG, then quality cannot be greater than 100.
			if( $this->format == 'PNG' && $this->quality > 9 )
				$this->quality = 9;
			else if( $this->format == 'JPG' && $this->quality > 100 )
				$this->quality = 100;
		}

		// *********************************************
		//	setQuality
		/*!
			@brief Set image quality. This is used only if
				we create JPG or PNG.
		*/
		// *********************************************
		public function setQuality( $val )
		{
			$this->quality = $val;
		}

		// *********************************************
		//	setFormat
		/*!
			@brief Set output format for resized image
		
			@param $val Output format type. Acceptable
				file formats are: PNG, JPG, GIF
		*/
		// *********************************************
		public function setFormat( $val )
		{
			$val = strtoupper( $val );
			$ok = array( 'PNG', 'JPG', 'GIF' );

			if( in_array( $val, $ok ) )
				$this->format = $val;
		}
	}

?>
