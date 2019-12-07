<?php
namespace App\Message;

class ReprocessMediaMessage extends AbstractMessage
{
    /** @var int The numeric identifier for the StationMedia record being processed. */
    public int $media_id;

    /** @var bool Whether to force reprocessing even if checks indicate it is not necessary. */
    public bool $force = false;
}
