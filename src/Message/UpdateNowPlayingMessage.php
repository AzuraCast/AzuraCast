<?php
namespace App\Message;

class UpdateNowPlayingMessage extends AbstractDelayedMessage
{
    public int $station_id;

    public function __construct()
    {
        $this->delay = self::ONE_SEC * 2;
    }
}
