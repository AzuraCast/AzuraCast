<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Time;
use App\Utilities\Types;

class StationMediaMetadata extends AbstractStationConfiguration
{
    public const string AMPLIFY = 'amplify';

    public function getAmplify(): ?float
    {
        return Types::floatOrNull($this->get(self::AMPLIFY));
    }

    public function setAmplify(float|string $amplify = null): void
    {
        $this->set(self::AMPLIFY, self::getNumericValue($amplify, true));
    }

    public const string CROSS_START_NEXT = 'cross_start_next';

    public function getCrossStartNext(): ?float
    {
        return Types::floatOrNull($this->get(self::CROSS_START_NEXT));
    }

    public function setCrossStartNext(string|int|float $startNext = null): void
    {
        $this->set(self::CROSS_START_NEXT, self::getNumericValue($startNext));
    }

    public const string FADE_IN = 'fade_in';

    public function getFadeIn(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_IN));
    }

    public function setFadeIn(string|int|float $fadeIn = null): void
    {
        $this->set(self::FADE_IN, self::getNumericValue($fadeIn));
    }

    public const string FADE_OUT = 'fade_out';

    public function getFadeOut(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_OUT));
    }

    public function setFadeOut(string|int|float $fadeOut = null): void
    {
        $this->set(self::FADE_OUT, self::getNumericValue($fadeOut));
    }

    public const string CUE_IN = 'cue_in';

    public function getCueIn(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_IN));
    }

    public function setCueIn(string|int|float $cueIn = null): void
    {
        $this->set(self::CUE_IN, self::getNumericValue($cueIn));
    }

    public const string CUE_OUT = 'cue_out';

    public function getCueOut(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_OUT));
    }

    public function setCueOut(string|int|float $cueOut = null): void
    {
        $this->set(self::CUE_OUT, self::getNumericValue($cueOut));
    }

    private function getNumericValue(
        string|int|float $annotation = null,
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
