<?php

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_NowPlayingLive")
 */
class NowPlayingLive
{
    /**
     * Whether the stream is known to currently have a live DJ.
     *
     * @OA\Property(example=false)
     * @var bool
     */
    public bool $is_live = false;

    /**
     * The current active streamer/DJ, if one is available.
     *
     * @OA\Property(example="DJ Jazzy Jeff")
     * @var string
     */
    public string $streamer_name = '';

    /**
     * The start timestamp of the current broadcast, if one is available.
     *
     * @OA\Property(example="1591548318")
     * @var int|null
     */
    public ?int $broadcast_start = null;

    public function __construct($is_live = false, $streamer_name = '', $broadcast_start = null)
    {
        $this->is_live = (bool)$is_live;
        $this->streamer_name = (string)$streamer_name;
        $this->broadcast_start = $broadcast_start;
    }
}
