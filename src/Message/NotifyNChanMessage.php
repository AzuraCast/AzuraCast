<?php
namespace App\Message;

use App\Entity\Api\NowPlaying;
use App\Entity\SongHistory;

class NotifyNChanMessage extends AbstractDelayedMessage
{
    /** @var int */
    public $station_id;
    /** @var string */
    public $station_shortcode;
    /** @var NowPlaying */
    public $nowplaying;

    public function __construct()
    {
        $this->delay = self::ONE_SEC * SongHistory::PLAYBACK_DELAY_SECONDS;
    }
}
