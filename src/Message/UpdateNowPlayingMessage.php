<?php
namespace App\Message;

class UpdateNowPlayingMessage extends AbstractDelayedMessage
{
    public function __construct()
    {
        $this->delay = self::ONE_SEC;
    }

    /** @var int */
    public $station_id;

    /** @var array */
    public $extra_metadata = [];
}