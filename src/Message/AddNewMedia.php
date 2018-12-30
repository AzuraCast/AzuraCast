<?php
namespace App\Message;

class AddNewMedia extends AbstractMessage
{
    /** @var int The numeric identifier for the station. */
    public $station_id;

    /** @var string The relative path for the media file to be processed. */
    public $path;
}
