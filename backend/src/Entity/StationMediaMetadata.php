<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Time;
use App\Utilities\Types;

class StationMediaMetadata extends AbstractStationConfiguration
{
    protected function getValidKeys(): array
    {
        return array_keys($this->data);
    }

    protected function set(string $key, mixed $value): static
    {
        if (!self::isLiquidsoapAnnotation($key)) {
            return $this;
        }

        return parent::set($key, $value);
    }

    public const string AMPLIFY = 'liq_amplify';

    public function getLiqAmplify(): ?float
    {
        return Types::floatOrNull($this->get(self::AMPLIFY));
    }

    public function setLiqAmplify(float|string $amplify = null): void
    {
        $this->set(self::AMPLIFY, self::getNumericValue($amplify));
    }

    public const string CROSS_START_NEXT = 'liq_cross_start_next';

    public function getLiqCrossStartNext(): ?float
    {
        return Types::floatOrNull($this->get(self::CROSS_START_NEXT));
    }

    public function setLiqCrossStartNext(string|int|float $startNext = null): void
    {
        $this->set(self::CROSS_START_NEXT, self::getNumericValue($startNext));
    }

    public const string FADE_IN = 'liq_fade_in';

    public function getLiqFadeIn(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_IN));
    }

    public function setLiqFadeIn(string|int|float $fadeIn = null): void
    {
        $this->set(self::FADE_IN, self::getNumericValue($fadeIn));
    }

    public const string FADE_OUT = 'liq_fade_out';

    public function getLiqFadeOut(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_OUT));
    }

    public function setLiqFadeOut(string|int|float $fadeOut = null): void
    {
        $this->set(self::FADE_OUT, $fadeOut);
    }

    public const string CUE_IN = 'liq_cue_in';

    public function getLiqCueIn(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_IN));
    }

    public function setLiqCueIn(string|int|float $cueIn = null): void
    {
        $this->set(self::CUE_IN, self::getNumericValue($cueIn));
    }

    public const string CUE_OUT = 'liq_cue_out';

    public function getLiqCueOut(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_OUT));
    }

    public function setLiqCueOut(string|int|float $cueOut = null): void
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

    public static function isLiquidsoapAnnotation(string $key): bool
    {
        return str_starts_with($key, 'liq_')
            || str_starts_with($key, 'replaygain_');
    }
}
