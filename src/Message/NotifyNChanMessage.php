<?php
namespace App\Message;

use App\Entity\Api\NowPlaying;
use App\Entity\SongHistory;

class NotifyNChanMessage extends AbstractDelayedMessage
{
    public int $station_id;

    public string $station_shortcode;

    public NowPlaying $nowplaying;

    public function __construct()
    {
        $this->delay = self::ONE_SEC * SongHistory::PLAYBACK_DELAY_SECONDS;
    }
}
