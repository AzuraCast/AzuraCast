<?php
namespace App\Message;

use App\Entity\Api\NowPlaying;
use App\Entity\SongHistory;

class NotifyNChanMessage extends AbstractDelayedMessage
{
    public function __construct()
    {
        $this->delay = self::ONE_SEC*SongHistory::PLAYBACK_DELAY_SECONDS;
    }

    /** @var int */
    public $station_id;

    /** @var NowPlaying */
    public $nowplaying;
}
