<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class StationMount
{
    /**
     * Mount point name/URL
     *
     * @SWG\Property(example="/radio.mp3")
     * @var string
     */
    public $name;

    /**
     * If the mount is the default mount for the parent station
     *
     * @SWG\Property(example=true)
     * @var bool
     */
    public $is_default;

    /**
     * Full listening URL specific to this mount
     *
     * @SWG\Property(example="http://localhost:8000/radio.mp3")
     * @var string
     */
    public $url;

    /**
     * Bitrate (kbps) of the broadcasted audio (if known)
     *
     * @SWG\Property(example=128)
     * @var int
     */
    public $bitrate;

    /**
     * Audio encoding format of broadcasted audio (if known)
     *
     * @SWG\Property(example="mp3")
     * @var string
     */
    public $format;
}