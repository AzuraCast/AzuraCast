<?php

namespace App\Entity\Api;

use App\Http\Router;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Package")
 */
class Package
{

    /**
     * Package ID
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * Package Reseller
     * @OA\Property(example=1)
     */
    public int $user_id;

    /**
     * Package name
     * @OA\Property(example="AzuraTest Radio")
     */
    public string $name;

    /**
     * Which broadcasting software (frontend) the package is for
     * @OA\Property(example="shoutcast2")
     */
    public string $frontend = '';

    /**
     * If the package can be used for new stations
     *
     * @OA\Property(example=true)
     */
    public bool $is_enabled = true;

    /**
     * Bitrate for stations
     * @OA\Property(example=320)
     */
    public int $bitrate;

    /**
     * Max listeners for stations
     * @OA\Property(example=0)
     */
    public int $max_listeners;

    /**
     * @OA\Property()
     * @var Station[]
     */
    public array $stations = [];

}
