<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Strings;
use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "StationFrontendConfiguration", type: "object")]
final class StationFrontendConfiguration extends AbstractArrayEntity
{
    public function __construct(array $elements = [])
    {
        // Generate defaults if not set.
        $autoAssignPasswords = [
            'source_pw',
            'admin_pw',
            'relay_pw',
            'streamer_pw',
        ];

        foreach ($autoAssignPasswords as $autoAssignPassword) {
            if (empty($elements[$autoAssignPassword])) {
                $elements[$autoAssignPassword] = Strings::generatePassword();
            }
        }

        parent::__construct($elements);
    }

    #[OA\Property]
    public ?string $custom_config = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public string $source_pw;

    #[OA\Property]
    public string $admin_pw;

    #[OA\Property]
    public string $relay_pw;

    #[OA\Property]
    public string $streamer_pw;

    #[OA\Property]
    public ?int $port = null {
        set (int|string|null $value) => Types::intOrNull($value);
    }

    #[OA\Property]
    public ?int $max_listeners = null {
        set (int|string|null $value) => Types::intOrNull($value);
    }

    #[OA\Property]
    public ?string $banned_ips = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $banned_user_agents = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property(
        items: new OA\Items(type: 'string'),
    )]
    public ?array $banned_countries = null;

    #[OA\Property]
    public ?string $allowed_ips = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $sc_license_id = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $sc_user_id = null {
        set => Types::stringOrNull($value, true);
    }
}
