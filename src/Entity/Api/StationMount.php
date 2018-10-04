<?php
namespace App\Entity\Api;

use App\Http\Router;

/**
 * @OA\Schema(type="object")
 */
class StationMount
{
    /**
     * Mount point name/URL
     *
     * @OA\Property(example="/radio.mp3")
     * @var string
     */
    public $name;

    /**
     * If the mount is the default mount for the parent station
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public $is_default;

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

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        $this->url = (string)$router->getUri($this->url, true);
    }
}
