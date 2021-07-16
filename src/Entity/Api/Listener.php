<?php

declare(strict_types=1);

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
     * The listener's client details (extracted from user-agent)
     *
     * @OA\Property(example="")
     * @var string
     */
    public string $client = '';

    /**
     * Whether the user-agent is likely a mobile browser.
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $is_mobile = false;

    /**
     * Whether the user is connected to a local mount point or a remote one.
     *
     * @OA\Property(example=false)
     * @var bool
     */
    public bool $mount_is_local = false;

    /**
     * The display name of the mount point.
     *
     * @OA\Property(example="/radio.mp3")
     * @var string
     */
    public string $mount_name = '';

    /**
     * UNIX timestamp that the user first connected.
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public int $connected_on;

    /**
     * UNIX timestamp that the user disconnected (or the latest timestamp if they are still connected).
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public int $connected_until;

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
