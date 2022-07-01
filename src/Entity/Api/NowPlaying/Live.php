<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrlInterface;
use App\Http\Router;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_NowPlaying_Live',
    type: 'object'
)]
final class Live implements ResolvableUrlInterface
{
    #[OA\Property(
        description: 'Whether the stream is known to currently have a live DJ.',
        example: false
    )]
    public bool $is_live = false;

    #[OA\Property(
        description: 'The current active streamer/DJ, if one is available.',
        example: 'DJ Jazzy Jeff'
    )]
    public string $streamer_name = '';

    #[OA\Property(
        description: 'The start timestamp of the current broadcast, if one is available.',
        example: '1591548318'
    )]
    public ?int $broadcast_start = null;

    #[OA\Property(
        description: 'URL to the streamer artwork (if available).',
        example: 'https://picsum.photos/1200/1200',
        nullable: true
    )]
    public string|UriInterface|null $art = null;

    public function resolveUrls(UriInterface $base): void
    {
        $this->art = (null !== $this->art)
            ? (string)Router::resolveUri($base, $this->art, true)
            : null;
    }
}
