<?php
/**
 * Image management utility class.
 */

namespace DF;
class Image
{
	public static function resizeImage($source_file, $dest_file, $width, $height)
	{
		if (!is_readable($source_file))
			$source_file = File::getFilePath($source_file);
		if (!is_readable($dest_file))
			$dest_file = File::getFilePath($dest_file);
		
		if (!file_exists($source_file))
			throw new Exception('Original image file not found!');
		
		$source_extension = strtolower(File::getFileExtension($source_file));
		$dest_extension = strtolower(File::getFileExtension($dest_file));

		switch($source_extension)
		{
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg($source_file);
			break;
			
			case 'gif':
				$image = imagecreatefromgif($source_file);
			break;
			
			case 'png':
				$image = imagecreatefrompng($source_file);
			break;
		}
		
		$image_width = imagesx($image);
		$image_height = imagesy($image);
		
		// Don't resize if the uploaded picture if smaller than the requirements.
		if ($image_width <= $width && $image_height <= $height)
		{
			$resized_image = $image;
		}
		else
		{
			// Create file resized to the proper proportions.
			$resized_ratio_width = $width / $image_width;
			$resized_ratio_height = $height / $image_height;
			$resized_ratio = min($resized_ratio_width, $resized_ratio_height);
			
			$resized_width = round($image_width * $resized_ratio);
			$resized_height = round($image_height * $resized_ratio);

			$resized_image = imagecreatetruecolor($resized_width, $resized_height);

			if ($dest_extension == 'png')
			{
				imagealphablending($resized_image, false);
				imagesavealpha($resized_image, true);

				$transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
				imagefilledrectangle($resized_image, 0, 0, $resized_width, $resized_height, $transparent);
			}

			imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $resized_width, $resized_height, $image_width, $image_height);
		}
		
		switch($dest_extension)
		{
			case 'jpg':
			case 'jpeg':
				imagejpeg($resized_image, $dest_file, 90);
			break;
			
			case 'gif':
				imagegif($resized_image, $dest_file);
			break;
			
			case 'png':
				imagepng($resized_image, $dest_file, 0);
			break;
		}
	}	
}