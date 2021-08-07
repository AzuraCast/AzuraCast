<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_StationRemote")
 */
class StationRemote
{
    /**
     * Mount/Remote ID number.
     *
     * @OA\Property(example=1)
     * @var int
     */
    public int $id;

    /**
     * Mount point name/URL
     *
     * @OA\Property(example="/radio.mp3")
     * @var string
     */
    public string $name;

    /**
     * Full listening URL specific to this mount
     *
     * @OA\Property(example="http://localhost:8000/radio.mp3")
     * @var string|UriInterface
     */
    public $url;

    /**
     * Bitrate (kbps) of the broadcasted audio (if known)
     *
     * @OA\Property(example=128)
     * @var int|null
     */
    public ?int $bitrate = null;

    /**
     * Audio encoding format of broadcasted audio (if known)
     *
     * @OA\Property(example="mp3")
     * @var string|null
     */
    public ?string $format = null;

    /**
     * Listener details
     *
     * @OA\Property
     * @var NowPlayingListeners
     */
    public NowPlayingListeners $listeners;
}
