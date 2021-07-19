<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\Router;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_Song")
 */
class Song implements ResolvableUrlInterface
{
    /**
     * The song's 32-character unique identifier hash
     *
     * @OA\Property(example="9f33bbc912c19603e51be8e0987d076b")
     * @var string
     */
    public string $id = '';

    /**
     * The song title, usually "Artist - Title"
     *
     * @OA\Property(example="Chet Porter - Aluko River")
     * @var string
     */
    public string $text = '';

    /**
     * The song artist.
     *
     * @OA\Property(example="Chet Porter")
     * @var string
     */
    public string $artist = '';

    /**
     * The song title.
     *
     * @OA\Property(example="Aluko River")
     * @var string
     */
    public string $title = '';

    /**
     * The song album.
     *
     * @OA\Property(example="Moving Castle")
     * @var string
     */
    public string $album = '';

    /**
     * The song genre.
     *
     * @OA\Property(example="Rock")
     * @var string
     */
    public string $genre = '';

    /**
     * Lyrics to the song.
     *
     * @OA\Property(example="")
     * @var string
     */
    public string $lyrics = '';

    /**
     * URL to the album artwork (if available).
     *
     * @OA\Property(example="https://picsum.photos/1200/1200")
     * @var string|UriInterface
     */
    public $art = '';

    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="custom_field_value"
     *     )
     * )
     * @var array
     */
    public $custom_fields = [];

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->art = (string)Router::resolveUri($base, $this->art, true);
    }
}
