<?php
namespace App\Entity\Api;

/**
 * @OA\Schema(type="object")
 */
class StationRemote
{
    /**
     * Full listening URL specific to this mount
     *
     * @OA\Property(example="http://localhost:8000/radio.mp3")
     * @var string
     */
    public $url;

    /**
     * Bitrate (kbps) of the broadcasted audio (if known)
     *
     * @OA\Property(example=128)
     * @var int
     */
    public $bitrate;

    /**
     * Audio encoding format of broadcasted audio (if known)
     *
     * @OA\Property(example="mp3")
     * @var string
     */
    public $format;
}
