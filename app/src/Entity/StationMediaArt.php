<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Table(name="station_media_art")
 * @Entity
 */
class StationMediaArt
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="media_id", type="integer")
     * @var int
     */
    protected $media_id;

    /**
     * @OneToOne(targetEntity="StationMedia", inversedBy="art")
     * @JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * @var StationMedia
     */
    protected $media;

    /**
     * @Column(name="art", type="blob", nullable=true)
     * @var resource|null
     */
    protected $art;

    public function __construct(StationMedia $media)
    {
        $this->media = $media;
    }

    public function getMedia(): StationMedia
    {
        return $this->media;
    }

    /**
     * @return null|resource
     */
    public function getArt()
    {
        return $this->art;
    }

    /**
     * @param resource $source_image_path A GD image manipulation resource.
     * @return bool
     */
    public function setArt($source_gd_image = null)
    {
        if (!is_resource($source_gd_image)) {
            return false;
        }

        $dest_max_width = 1200;
        $dest_max_height = 1200;

        $source_image_width = imagesx($source_gd_image);
        $source_image_height = imagesy($source_gd_image);

        $source_aspect_ratio = $source_image_width / $source_image_height;
        $thumbnail_aspect_ratio = $dest_max_width / $dest_max_height;

        if ($source_image_width <= $dest_max_width && $source_image_height <= $dest_max_height) {
            $thumbnail_image_width = $source_image_width;
            $thumbnail_image_height = $source_image_height;
        } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
            $thumbnail_image_width = (int) ($dest_max_height * $source_aspect_ratio);
            $thumbnail_image_height = $dest_max_height;
        } else {
            $thumbnail_image_width = $dest_max_width;
            $thumbnail_image_height = (int) ($dest_max_width / $source_aspect_ratio);
        }

        $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
        imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);

        ob_start();
        imagejpeg($thumbnail_gd_image, NULL, 90);
        $this->art = ob_get_contents();
        ob_end_clean();

        imagedestroy($source_gd_image);
        imagedestroy($thumbnail_gd_image);
        return true;
    }
}