<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Time;
use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "StationMediaMetadata", type: "object")]
final class StationMediaMetadata extends AbstractArrayEntity
{
    public const string AMPLIFY = 'amplify';

    #[OA\Property(
        description: 'Value (in dB) to amplify the current track to produce a uniform loudness.',
        example: '-1.5'
    )]
    public ?float $amplify = null {
        set (float|string|null $value) => self::getNumericValue($value, true);
    }

    public const string FADE_IN = 'fade_in';

    #[OA\Property(
        description: 'Seconds from the start of the track to end fading in.',
        example: '2.0'
    )]
    public ?float $fade_in = null {
        set (string|int|float|null $value) => self::getNumericValue($value);
    }

    public const string FADE_OUT = 'fade_out';

    #[OA\Property(
        description: 'Seconds from the end of the track to begin fading out.',
        example: '2.0'
    )]
    public ?float $fade_out = null {
        set (string|int|float|null $value) => self::getNumericValue($value);
    }

    public const string CUE_IN = 'cue_in';

    #[OA\Property(
        description: 'Seconds from the start of the track to start playback (cue in).',
        example: '3.5'
    )]
    public ?float $cue_in = null {
        set (string|int|float|null $value) => self::getNumericValue($value);
    }

    public const string CUE_OUT = 'cue_out';

    #[OA\Property(
        description: 'Seconds from the start of the track to end playback (cue out).',
        example: '181.5'
    )]
    public ?float $cue_out = null {
        set (string|int|float|null $value) => self::getNumericValue($value);
    }

    public const string CROSS_START_NEXT = 'cross_start_next';

    #[OA\Property(
        description: 'Seconds from the start of the track to begin fading in the next track.',
        example: '180.0'
    )]
    public ?float $cross_start_next = null {
        set (string|int|float|null $value) => self::getNumericValue($value);
    }

    protected static function getNumericValue(
        string|int|float|null $annotation = null,
        bool $allowNegative = false
    ): ?float {
        if (is_string($annotation)) {
            if (str_contains($annotation, ':')) {
                $annotation = Time::displayTimeToSeconds($annotation);
            } else {
                preg_match('/([+-]?\d*\.?\d+)/', $annotation, $matches);
                $annotation = $matches[1] ?? null;
            }
        }

        $annotation = Types::floatOrNull($annotation);

        if (null === $annotation) {
            return null;
        }

        return ($allowNegative || $annotation >= 0)
            ? $annotation
            : null;
    }
}
