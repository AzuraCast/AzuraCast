<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrlInterface;
use App\Http\Router;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_NowPlaying_Station',
    type: 'object'
)]
final class Station implements ResolvableUrlInterface
{
    #[OA\Property(
        description: 'Station ID',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'Station name',
        example: 'AzuraTest Radio'
    )]
    public string $name;

    #[OA\Property(
        description: 'Station "short code", used for URL and folder paths',
        example: 'azuratest_radio'
    )]
    public string $shortcode = '';

    #[OA\Property(
        description: 'Station description',
        example: 'An AzuraCast station!'
    )]
    public string $description = '';

    #[OA\Property(
        description: 'Which broadcasting software (frontend) the station uses',
        example: 'shoutcast2'
    )]
    public string $frontend = '';

    #[OA\Property(
        description: 'Which AutoDJ software (backend) the station uses',
        example: 'liquidsoap'
    )]
    public string $backend = '';

    #[OA\Property(
        description: 'The full URL to listen to the default mount of the station',
        example: 'http://localhost:8000/radio.mp3'
    )]
    public string|UriInterface|null $listen_url;

    #[OA\Property(
        description: 'The public URL of the station.',
        example: 'https://example.com/'
    )]
    public ?string $url = null;

    #[OA\Property(
        description: 'The public player URL for the station.',
        example: 'https://example.com/public/example_station'
    )]
    public string|UriInterface $public_player_url;

    #[OA\Property(
        description: 'The playlist download URL in PLS format.',
        example: 'https://example.com/public/example_station/playlist.pls'
    )]
    public string|UriInterface $playlist_pls_url;

    #[OA\Property(
        description: 'The playlist download URL in M3U format.',
        example: 'https://example.com/public/example_station/playlist.m3u'
    )]
    public string|UriInterface $playlist_m3u_url;

    #[OA\Property(
        description: 'If the station is public (i.e. should be shown in listings of all stations)',
        example: true
    )]
    public bool $is_public = false;

    /** @var StationMount[] */
    #[OA\Property]
    public array $mounts = [];

    /** @var StationRemote[] */
    #[OA\Property]
    public array $remotes = [];

    #[OA\Property(
        description: 'If the station has HLS streaming enabled.',
        example: true
    )]
    public bool $hls_enabled = false;

    #[OA\Property(
        description: 'If the HLS stream should be the default one for the station.',
        example: true
    )]
    public bool $hls_is_default = false;

    #[OA\Property(
        description: 'The full URL to listen to the HLS stream for the station.',
        example: 'https://example.com/hls/azuratest_radio/live.m3u8',
        nullable: true
    )]
    public string|UriInterface|null $hls_url = null;

    #[OA\Property(
        description: 'HLS Listeners',
        example: 1
    )]
    public int $hls_listeners = 0;

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->listen_url = (null !== $this->listen_url)
            ? (string)Router::resolveUri($base, $this->listen_url, true)
            : null;

        $this->public_player_url = (string)Router::resolveUri($base, $this->public_player_url, true);
        $this->playlist_pls_url = (string)Router::resolveUri($base, $this->playlist_pls_url, true);
        $this->playlist_m3u_url = (string)Router::resolveUri($base, $this->playlist_m3u_url, true);

        foreach ($this->mounts as $mount) {
            if ($mount instanceof ResolvableUrlInterface) {
                $mount->resolveUrls($base);
            }
        }

        $this->hls_url = (null !== $this->hls_url)
            ? (string)Router::resolveUri($base, $this->hls_url, true)
            : null;
    }
}
