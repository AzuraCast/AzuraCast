<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrlInterface;
use App\Http\Router;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_NowPlaying_StationMount',
    type: 'object'
)]
final class StationMount extends StationRemote implements ResolvableUrlInterface
{
    #[OA\Property(
        description: 'The relative path that corresponds to this mount point',
        example: '/radio.mp3'
    )]
    public string $path;

    #[OA\Property(
        description: 'If the mount is the default mount for the parent station',
        example: true
    )]
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
