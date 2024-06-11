<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_ListenerDevice',
    type: 'object'
)]
final class ListenerDevice
{
    #[OA\Property(
        description: 'If the listener device is likely a browser.',
        example: true
    )]
    public bool $is_browser;

    #[OA\Property(
        description: 'If the listener device is likely a mobile device.',
        example: true
    )]
    public bool $is_mobile;

    #[OA\Property(
        description: 'If the listener device is likely a crawler.',
        example: true
    )]
    public bool $is_bot;

    #[OA\Property(
        description: 'Summary of the listener client.',
        example: 'Firefox 121.0, Windows'
    )]
    public ?string $client = null;

    #[OA\Property(
        description: 'Summary of the listener browser family.',
        example: 'Firefox'
    )]
    public ?string $browser_family = null;

    #[OA\Property(
        description: 'Summary of the listener OS family.',
        example: 'Windows'
    )]
    public ?string $os_family = null;

    public static function fromArray(array $row): self
    {
        $api = new self();
        $api->is_browser = Types::bool($row['is_browser'] ?? null);
        $api->is_mobile = Types::bool($row['is_mobile'] ?? null);
        $api->is_bot = Types::bool($row['is_bot'] ?? null);
        $api->client = Types::stringOrNull($row['client'] ?? null);
        $api->browser_family = Types::stringOrNull($row['browser_family'] ?? null);
        $api->os_family = Types::stringOrNull($row['os_family'] ?? null);
        return $api;
    }
}
