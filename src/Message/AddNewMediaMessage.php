<?php
namespace App\Message;

class AddNewMediaMessage extends AbstractMessage
{
    /** @var int The numeric identifier for the station. */
    public int $station_id;

    /** @var string The relative path for the media file to be processed. */
    public string $path;
}
