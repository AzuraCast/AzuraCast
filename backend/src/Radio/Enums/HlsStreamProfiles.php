<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum HlsStreamProfiles: string
{
    case AacLowComplexity = 'aac';
    case AacHighEfficiencyV1 = 'aac_he';
    case AacHighEfficiencyV2 = 'aac_he_v2';

    public function getProfileName(): string
    {
        return match ($this) {
            self::AacLowComplexity => 'aac_low',
            default => $this->value
        };
    }

    public static function default(): self
    {
        return self::AacLowComplexity;
    }
}
