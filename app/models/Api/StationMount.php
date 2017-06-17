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
     * @SWG\Property
     * @var string
     */
    public $name;

    /**
     * If the mount is the default mount for the parent station
     *
     * @SWG\Property
     * @var bool
     */
    public $is_default;

    /**
     * Full listening URL specific to this mount
     *
     * @SWG\Property
     * @var string
     */
    public $url;

    /**
     * Bitrate (kbps) of the broadcasted audio (if known)
     *
     * @SWG\Property
     * @var int
     */
    public $bitrate;

    /**
     * Audio encoding format of broadcasted audio (if known)
     *
     * @SWG\Property
     * @var string
     */
    public $format;
}