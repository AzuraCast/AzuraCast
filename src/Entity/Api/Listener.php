<?php

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Listener")
 */
class Listener
{
    /**
     * The listener's IP address
     *
     * @OA\Property(example="127.0.0.1")
     * @var string
     */
    public string $ip;

    /**
     * The listener's HTTP User-Agent
     *
     * phpcs:disable Generic.Files.LineLength
     * @OA\Property(example="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.86 Safari/537.36")
     * @var string
     * phpcs:enable
     */
    public string $user_agent = '';

    /**
     * Whether the user-agent is likely a mobile browser.
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $is_mobile = false;

    /**
     * UNIX timestamp that the user first connected.
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public int $connected_on;

    /**
     * Number of seconds that the user has been connected.
     *
     * @OA\Property(example=30)
     * @var int
     */
    public int $connected_time = 0;

    /**
     * Location metadata, if available
     *
     * @OA\Property(@OA\Items)
     * @var array
     */
    public array $location = [];
}
