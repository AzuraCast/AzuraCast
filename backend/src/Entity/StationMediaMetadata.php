<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Time;
use App\Utilities\Types;

final class StationMediaMetadata extends AbstractArrayEntity
{
    public const string AMPLIFY = 'amplify';

    public ?float $amplify {
        get => Types::floatOrNull($this->get(__PROPERTY__));
        set (float|string|null $value) {
            $this->set(__PROPERTY__, self::getNumericValue($value, true));
        }
    }

    public const string CROSS_START_NEXT = 'cross_start_next';

    public ?float $cross_start_next {
        get => Types::floatOrNull($this->get(__PROPERTY__));
        set (string|int|float|null $value) {
            $this->set(__PROPERTY__, self::getNumericValue($value));
        }
    }

    public const string FADE_IN = 'fade_in';

    public ?float $fade_in {
        get => Types::floatOrNull($this->get(__PROPERTY__));
        set (string|int|float|null $value) {
            $this->set(__PROPERTY__, self::getNumericValue($value));
        }
    }

    public const string FADE_OUT = 'fade_out';

    public ?float $fade_out {
        get => Types::floatOrNull($this->get(__PROPERTY__));
        set (string|int|float|null $value) {
            $this->set(__PROPERTY__, self::getNumericValue($value));
        }
    }

    public const string CUE_IN = 'cue_in';

    public ?float $cue_in {
        get => Types::floatOrNull($this->get(__PROPERTY__));
        set (string|int|float|null $value) {
            $this->set(__PROPERTY__, self::getNumericValue($value));
        }
    }

    public const string CUE_OUT = 'cue_out';

    public ?float $cue_out {
        get => Types::floatOrNull($this->get(__PROPERTY__));
        set (string|int|float|null $value) {
            $this->set(__PROPERTY__, self::getNumericValue($value));
        }
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
