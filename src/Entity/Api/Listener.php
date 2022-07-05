<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\OpenApi;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Listener',
    type: 'object'
)]
final class Listener
{
    #[OA\Property(
        description: 'The listener\'s IP address',
        example: '127.0.0.1'
    )]
    public string $ip;

    #[OA\Property(
        description: 'The listener\'s HTTP User-Agent',
        example: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)'
        . ' Chrome/59.0.3071.86 Safari/537.36'
    )]
    public string $user_agent = '';

    #[OA\Property(
        description: 'A unique identifier for this listener/user agent (used for unique calculations).',
        example: ''
    )]
    public string $hash = '';

    #[OA\Property(
        description: 'Whether the user is connected to a local mount point or a remote one.',
        example: false
    )]
    public bool $mount_is_local = false;

    #[OA\Property(
        description: 'The display name of the mount point.',
        example: '/radio.mp3'
    )]
    public string $mount_name = '';

    #[OA\Property(
        description: 'UNIX timestamp that the user first connected.',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $connected_on;

    #[OA\Property(
        description: 'UNIX timestamp that the user disconnected (or the latest timestamp if they are still connected).',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $connected_until;

    #[OA\Property(
        description: 'Number of seconds that the user has been connected.',
        example: 30
    )]
    public int $connected_time = 0;

    #[OA\Property(
        description: 'Device metadata, if available',
        items: new OA\Items()
    )]
    public array $device = [];

    #[OA\Property(
        description: 'Location metadata, if available',
        items: new OA\Items()
    )]
    public array $location = [];

    public static function fromArray(array $row): self
    {
        $api = new self();
        $api->ip = $row['listener_ip'];
        $api->user_agent = $row['listener_user_agent'];
        $api->hash = $row['listener_hash'];
        $api->connected_on = $row['timestamp_start'];
        $api->connected_until = $row['timestamp_end'];
        $api->connected_time = $api->connected_until - $api->connected_on;

        $device = [];
        $location = [];
        foreach ($row as $key => $val) {
            if (str_starts_with($key, 'device.')) {
                $device[str_replace('device.', '', $key)] = $val;
            } elseif (str_starts_with($key, 'location.')) {
                $location[str_replace('location.', '', $key)] = $val;
            }
        }

        $api->device = $device;
        $api->location = $location;

        return $api;
    }
}
