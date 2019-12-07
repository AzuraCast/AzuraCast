<?php
namespace App\Message;

use App\Entity\Api\NowPlaying;
use App\Entity\SongHistory;

class NotifyNChanMessage extends AbstractDelayedMessage
{
    /** @var int */
    public int $station_id;
    /** @var string */
    public string $station_shortcode;
    /** @var NowPlaying */
    public NowPlaying $nowplaying;

    public function __construct()
    {
        $this->delay = self::ONE_SEC * SongHistory::PLAYBACK_DELAY_SECONDS;
    }
}
