<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class Song
{
    /**
     * The song's 32-character unique identifier hash
     *
     * @SWG\Property(example="9f33bbc912c19603e51be8e0987d076b")
     * @var string
     */
    public $id;

    /**
     * The song title, usually "Artist - Title"
     *
     * @SWG\Property(example="Chet Porter - Aluko River")
     * @var string
     */
    public $text;

    /**
     * The song artist.
     *
     * @SWG\Property(example="Chet Porter")
     * @var string
     */
    public $artist;

    /**
     * The song title.
     *
     * @SWG\Property(example="Aluko River")
     * @var string
     */
    public $title;
}