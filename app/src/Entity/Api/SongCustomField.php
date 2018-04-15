<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class SongCustomField
{
    /**
     * The name of the custom metadata field.
     *
     * @SWG\Property(example="Buy Song URL")
     * @var string
     */
    public $name;

    /**
     * The value of the custom metadata field for this song.
     *
     * @SWG\Property(example="http://songurl.example.com/")
     * @var string
     */
    public $value;
}