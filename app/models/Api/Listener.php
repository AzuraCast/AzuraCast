<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class Listener
{
    /**
     * The listener's IP address
     *
     * @SWG\Property(example="127.0.0.1")
     * @var string
     */
    public $ip;

    /**
     * The listener's HTTP User-Agent
     *
     * @SWG\Property(example="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.86 Safari/537.36")
     * @var string
     */
    public $user_agent;

    /**
     * Whether the user-agent is likely a mobile browser.
     *
     * @SWG\Property(example=true)
     * @var bool
     */
    public $is_mobile;

    /**
     * UNIX timestamp that the user first connected.
     *
     * @SWG\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $connected_on;

    /**
     * Number of seconds that the user has been connected.
     *
     * @SWG\Property(example=30)
     * @var int
     */
    public $connected_time;

    /**
     * Location metadata, if available
     *
     * @SWG\Property()
     * @var array
     */
    public $location;
}