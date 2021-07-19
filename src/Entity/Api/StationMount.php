<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\Router;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_StationMount")
 */
class StationMount extends StationRemote implements ResolvableUrlInterface
{
    /**
     * The relative path that corresponds to this mount point
     *
     * @OA\Property(example="/radio.mp3")
     * @var string
     */
    public string $path;

    /**
     * If the mount is the default mount for the parent station
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $is_default;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->url = (string)Router::resolveUri($base, $this->url, true);
    }
}
