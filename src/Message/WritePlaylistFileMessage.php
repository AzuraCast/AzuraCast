<?php
namespace App\Message;

class WritePlaylistFileMessage extends AbstractMessage
{
    /** @var int The numeric identifier for the StationPlaylist record being processed. */
    public $playlist_id;
}
