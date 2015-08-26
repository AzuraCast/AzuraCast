<?php
/**
 * Image management utility class.
 */

namespace DF;
class Image
{
    public static function resizeImage($source_file, $dest_file, $width, $height, $crop = false)
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
                $img = imagecreatefromjpeg($source_file);
            break;
            
            case 'gif':
                $img = imagecreatefromgif($source_file);
            break;

            case 'png':
                $img = imagecreatefrompng($source_file);
            break;

            default:
                throw new Exception('Image format not supported.');
            break;
        }

        list($w, $h) = getimagesize($source_file);

        if ($crop)
        {
            if($w < $width or $h < $height)
                $new = $img;

            $ratio = max($width/$w, $height/$h);
            $h = $height / $ratio;
            $x = ($w - $width / $ratio) / 2;
            $w = $width / $ratio;
        }
        else
        {
            if($w < $width and $h < $height)
                $new = $img;

            $ratio = min($width/$w, $height/$h);
            $width = $w * $ratio;
            $height = $h * $ratio;
            $x = 0;
        }

        if (!isset($new))
        {
            $new = imagecreatetruecolor($width, $height);

            // Preserve transparency
            if ($source_extension == "gif" || $source_extension == "png")
            {
                imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
                imagealphablending($new, false);
                imagesavealpha($new, true);
            }

            imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);
        }

        /*
         * Old transparency method, keep around:
        imagealphablending($resized_image, false);
        imagesavealpha($resized_image, true);

        $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
        imagefilledrectangle($resized_image, 0, 0, $resized_width, $resized_height, $transparent);
         */
        
        switch($dest_extension)
        {
            case 'jpg':
            case 'jpeg':
                imagejpeg($new, $dest_file, 90);
            break;
            
            case 'gif':
                imagegif($new, $dest_file);
            break;
            
            case 'png':
                imagepng($new, $dest_file, 5);
            break;
        }
    }   
}