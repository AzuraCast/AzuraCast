<?php

namespace App\Entity\Api;

use App\Entity;

/**
 * @SWG\Definition(type="object")
 */
class Time
{
    public function __construct($tz_info)
    {
        /** @var \DateTime $now_utc */
        $now_utc = $tz_info['now_utc'];

        /** @var \DateTime $now */
        $now = $tz_info['now'];

        $this->timestamp = time();

        $this->gmt_datetime = $now_utc->format('Y-m-d g:i:s');
        $this->gmt_date = $now_utc->format('F j, Y');
        $this->gmt_time = $now_utc->format('g:ia');
        $this->gmt_timezone = 'GMT';
        $this->gmt_timezone_abbr = 'GMT';

        $this->local_datetime = $now->format('Y-m-d g:i:s');
        $this->local_date = $now->format('F j, Y');
        $this->local_time = $now->format('g:ia');
        $this->local_timezone = $tz_info['code'];
        $this->local_timezone_abbr = $tz_info['abbr'];
    }

    /**
     * The current UNIX timestamp
     *
     * @SWG\Property(example=1497652397)
     * @var int
     */
    public $timestamp;

    /**
     * @SWG\Property(example="2017-06-16 10:33:17")
     * @var string
     */
    public $gmt_datetime;

    /**
     * @SWG\Property(example="June 16, 2017")
     * @var string
     */
    public $gmt_date;

    /**
     * @SWG\Property(example="10:33pm")
     * @var string
     */
    public $gmt_time;

    /**
     * @SWG\Property(example="GMT")
     * @var string
     */
    public $gmt_timezone;

    /**
     * @SWG\Property(example="GMT")
     * @var string
     */
    public $gmt_timezone_abbr;

    /**
     * @SWG\Property(example="2017-06-16 10:33:17")
     * @var string
     */
    public $local_datetime;

    /**
     * @SWG\Property(example="June 16, 2017")
     * @var string
     */
    public $local_date;

    /**
     * @SWG\Property(example="10:33pm")
     * @var string
     */
    public $local_time;

    /**
     * @SWG\Property(example="UTC")
     * @var string
     */
    public $local_timezone;

    /**
     * @SWG\Property(example="UTC")
     * @var string
     */
    public $local_timezone_abbr;
}
