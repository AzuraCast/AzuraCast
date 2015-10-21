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

        if ($w === $width && $h === $height)
        {
            $new = $img;

            // Preserve transparency
            if ($source_extension == "gif" || $source_extension == "png")
            {
                imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
                imagealphablending($new, false);
                imagesavealpha($new, true);
            }
        }
        else
        {
            if ($crop)
            {
                $width_new = $h * $width / $height;
                $height_new = $w * $height / $width;

                //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
                if($width_new > $width)
                {
                    $x = 0;
                    $y = (($h - $height_new) / 2);

                    $h = $height_new;
                }
                else
                {
                    $x = (($w - $width_new) / 2);
                    $y = 0;

                    $w = $width_new;
                }
            }
            else
            {
                $ratio = min($width/$w, $height/$h);
                $width = $w * $ratio;
                $height = $h * $ratio;

                $x = 0;
                $y = 0;
            }

            $new = imagecreatetruecolor($width, $height);

            // Preserve transparency
            if ($source_extension == "gif" || $source_extension == "png")
            {
                imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
                imagealphablending($new, false);
                imagesavealpha($new, true);
            }

            imagecopyresampled($new, $img, 0, 0, $x, $y, $width, $height, $w, $h);
        }
        
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