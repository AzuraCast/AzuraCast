<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\Router;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_Station")
 */
class Station implements ResolvableUrlInterface
{
    /**
     * Station ID
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * Station name
     * @OA\Property(example="AzuraTest Radio")
     */
    public string $name;

    /**
     * Station "short code", used for URL and folder paths
     * @OA\Property(example="azuratest_radio")
     */
    public string $shortcode = '';

    /**
     * Station description
     * @OA\Property(example="An AzuraCast station!")
     */
    public string $description = '';

    /**
     * Which broadcasting software (frontend) the station uses
     * @OA\Property(example="shoutcast2")
     */
    public string $frontend = '';

    /**
     * Which AutoDJ software (backend) the station uses
     * @OA\Property(example="liquidsoap")
     */
    public string $backend = '';

    /**
     * The full URL to listen to the default mount of the station
     * @OA\Property(example="http://localhost:8000/radio.mp3")
     * @var string|UriInterface
     */
    public $listen_url;

    /**
     * The public URL of the station.
     * @OA\Property(example="https://example.com/")
     */
    public ?string $url = null;

    /**
     * The public player URL for the station.
     * @OA\Property(example="https://example.com/public/example_station")
     * @var string|UriInterface
     */
    public $public_player_url;

    /**
     * The playlist download URL in PLS format.
     * @OA\Property(example="https://example.com/public/example_station/playlist.pls")
     * @var string|UriInterface
     */
    public $playlist_pls_url;

    /**
     * The playlist download URL in M3U format.
     * @OA\Property(example="https://example.com/public/example_station/playlist.m3u")
     * @var string|UriInterface
     */
    public $playlist_m3u_url;

    /**
     * If the station is public (i.e. should be shown in listings of all stations)
     * @OA\Property(example=true)
     */
    public bool $is_public = false;

    /**
     * @OA\Property()
     * @var StationMount[]
     */
    public array $mounts = [];

    /**
     * @OA\Property()
     * @var StationRemote[]
     */
    public array $remotes = [];

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->listen_url = (string)Router::resolveUri($base, $this->listen_url, true);

        $this->public_player_url = (string)Router::resolveUri($base, $this->public_player_url, true);
        $this->playlist_pls_url = (string)Router::resolveUri($base, $this->playlist_pls_url, true);
        $this->playlist_m3u_url = (string)Router::resolveUri($base, $this->playlist_m3u_url, true);

        foreach ($this->mounts as $mount) {
            if ($mount instanceof ResolvableUrlInterface) {
                $mount->resolveUrls($base);
            }
        }
    }
}
