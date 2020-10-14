<?php

namespace App\Media;

use RuntimeException;

class AlbumArt
{
    public const MAX_WIDTH = 1200;
    public const MAX_HEIGHT = 1200;

    public static function resize(string $rawArtString): string
    {
        $source_image_info = getimagesizefromstring($rawArtString);
        $source_image_width = $source_image_info[0] ?? 0;
        $source_image_height = $source_image_info[1] ?? 0;
        $source_mime_type = $source_image_info['mime'] ?? 'unknown';

        $dest_max_width = self::MAX_WIDTH;
        $dest_max_height = self::MAX_HEIGHT;

        $source_inside_dest = $source_image_width <= $dest_max_width && $source_image_height <= $dest_max_height;

        // Avoid GD entirely if it's already a JPEG within our parameters.
        if ($source_mime_type === 'image/jpeg' && $source_inside_dest) {
            $albumArt = $rawArtString;
        } else {
            $source_gd_image = imagecreatefromstring($rawArtString);

            if (!is_resource($source_gd_image)) {
                throw new RuntimeException('Cannot create image from string.');
            }

            // Crop the raw art to a 1200x1200 artboard.
            if ($source_inside_dest) {
                $thumbnail_gd_image = $source_gd_image;
            } else {
                $source_aspect_ratio = $source_image_width / $source_image_height;
                $thumbnail_aspect_ratio = $dest_max_width / $dest_max_height;

                if ($thumbnail_aspect_ratio > $source_aspect_ratio) {
                    $thumbnail_image_width = (int)($dest_max_height * $source_aspect_ratio);
                    $thumbnail_image_height = $dest_max_height;
                } else {
                    $thumbnail_image_width = $dest_max_width;
                    $thumbnail_image_height = (int)($dest_max_width / $source_aspect_ratio);
                }

                $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
                imagecopyresampled(
                    $thumbnail_gd_image,
                    $source_gd_image,
                    0,
                    0,
                    0,
                    0,
                    $thumbnail_image_width,
                    $thumbnail_image_height,
                    $source_image_width,
                    $source_image_height
                );
            }

            ob_start();
            imagejpeg($thumbnail_gd_image, null, 90);
            $albumArt = ob_get_clean();

            imagedestroy($source_gd_image);
            imagedestroy($thumbnail_gd_image);
        }

        return $albumArt;
    }
}
