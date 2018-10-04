<?php

namespace App\Entity\Api;

use App\Entity;
use App\Http\Router;

/**
 * @OA\Schema(type="object")
 */
class Song
{
    /**
     * The song's 32-character unique identifier hash
     *
     * @OA\Property(example="9f33bbc912c19603e51be8e0987d076b")
     * @var string
     */
    public $id;

    /**
     * The song title, usually "Artist - Title"
     *
     * @OA\Property(example="Chet Porter - Aluko River")
     * @var string
     */
    public $text;

    /**
     * The song artist.
     *
     * @OA\Property(example="Chet Porter")
     * @var string
     */
    public $artist;

    /**
     * The song title.
     *
     * @OA\Property(example="Aluko River")
     * @var string
     */
    public $title;

    /**
     * The song album.
     *
     * @OA\Property(example="Moving Castle")
     * @var string
     */
    public $album = "";

    /**
     * Lyrics to the song.
     *
     * @OA\Property(example="")
     * @var string
     */
    public $lyrics = "";

    /**
     * URL to the album artwork (if available).
     *
     * @OA\Property(example="https://picsum.photos/1200/1200")
     * @var string
     */
    public $art = "";

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
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        $this->art = (string)$router->getUri($this->art, true);
    }
}
