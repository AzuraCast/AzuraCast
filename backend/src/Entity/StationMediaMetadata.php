<?php

declare(strict_types=1);

namespace App\Entity;

use App\Media\Metadata;
use App\Utilities\Time;
use App\Utilities\Types;
use ReflectionObject;

class StationMediaMetadata extends AbstractStationConfiguration
{
    public const string AMPLIFY = 'amplify';

    public function getAmplify(): ?float
    {
        return Types::floatOrNull($this->get(self::AMPLIFY));
    }

    public function setAmplify(float|string $amplify = null): void
    {
        $this->set(self::AMPLIFY, self::getNumericValue($amplify));
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
        $this->set(self::FADE_OUT, $fadeOut);
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

    public static function getNumericValue(string|int|float $annotation = null): ?float
    {
        if (is_string($annotation)) {
            if (str_contains($annotation, ':')) {
                $annotation = Time::displayTimeToSeconds($annotation);
            } else {
                preg_match('/([+-]?\d*\.?\d+)/', $annotation, $matches);
                $annotation = $matches[1] ?? null;
            }
        }

        return Types::floatOrNull($annotation);
    }

    public function toAnnotations(float $duration): array
    {
        $annotations = array_filter(
            $this->toArray() ?? [],
            fn($row) => $row !== null
        );

        if (0 === count($annotations)) {
            return [];
        }

        // Safety checks for cue lengths.
        if (
            isset($annotations[self::CUE_OUT])
            && $annotations[self::CUE_OUT] < 0.0
        ) {
            $cueOut = abs($annotations[self::CUE_OUT]);

            if (0.0 === $cueOut) {
                unset($annotations[self::CUE_OUT]);
            }

            if ($cueOut > $duration) {
                unset($annotations[self::CUE_OUT]);
            } else {
                $annotations[self::CUE_OUT] = max(0, $duration - $cueOut);
            }
        }

        if (
            isset($annotations[self::CUE_OUT])
            && $annotations[self::CUE_OUT] > $duration
        ) {
            unset($annotations[self::CUE_OUT]);
        }

        if (
            isset($annotations[self::CUE_IN])
            && $annotations[self::CUE_IN] > $duration
        ) {
            unset($annotations[self::CUE_IN]);
        }

        // Specify formatting on Amplify.
        if (isset($annotations[self::AMPLIFY])) {
            $annotations[self::AMPLIFY] .= ' dB';
        }

        return $annotations;
    }
}
