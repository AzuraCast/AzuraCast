<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_UpdateDetails',
    type: 'object'
)]
final readonly class UpdateDetails
{
    public function __construct(
        #[OA\Property(
            description: 'The stable-equivalent branch your installation currently appears to be on.',
            example: '0.20.3'
        )]
        public ?string $current_release,
        #[OA\Property(
            description: 'The current latest stable release of the software.',
            example: '0.20.4'
        )]
        public ?string $latest_release,
        #[OA\Property(
            description: 'If you are on the Rolling Release, whether your installation needs to be updated.',
        )]
        public bool $needs_rolling_update = false,
        #[OA\Property(
            description: 'Whether a newer stable release is available than the version you are currently using.',
        )]
        public bool $needs_release_update = false,
        #[OA\Property(
            description: 'If you are on the Rolling Release, the number of updates released since your version.',
        )]
        public int $rolling_updates_available = 0,
        #[OA\Property(
            description: 'Whether you can seamlessly move from the Rolling Release channel to Stable without issues.',
        )]
        public bool $can_switch_to_stable = false
    ) {
    }

    public static function fromArray(array $updates): self
    {
        return new self(
            current_release: Types::stringOrNull($updates['current_release'] ?? null),
            latest_release: Types::stringOrNull($updates['latest_release'] ?? null),
            needs_rolling_update: Types::bool($updates['needs_rolling_update'] ?? false),
            needs_release_update: Types::bool($updates['needs_release_update'] ?? false),
            rolling_updates_available: Types::int($updates['rolling_updates_available'] ?? 0),
            can_switch_to_stable: Types::bool($updates['can_switch_to_stable'] ?? false)
        );
    }
}
