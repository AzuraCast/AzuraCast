<?php

namespace App\Entity\Api;

use App\Entity;

/**
 * @OA\Schema(type="object")
 */
class NowPlayingLive
{
    public function __construct($is_live = false, $streamer_name = '')
    {
        $this->is_live = (bool)$is_live;
        $this->streamer_name = (string)$streamer_name;
    }

    /**
     * Whether the stream is known to currently have a live DJ.
     *
     * @OA\Property(example=false)
     * @var bool
     */
    public $is_live;

    /**
     * The current active streamer/DJ, if one is available.
     *
     * @OA\Property(example="DJ Jazzy Jeff")
     * @var string
     */
    public $streamer_name;
}
