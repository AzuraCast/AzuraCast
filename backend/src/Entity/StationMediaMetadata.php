<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Time;
use App\Utilities\Types;

class StationMediaMetadata extends AbstractStationConfiguration
{
    protected bool $unrestricted = true;

    protected function set(string $key, mixed $value): static
    {
        if (!self::isValidKey($key)) {
            return $this;
        }

        return parent::set($key, $value);
    }

    public const string AMPLIFY = 'liq_amplify';

    public function getLiqAmplify(): ?float
    {
        return Types::floatOrNull($this->get(self::AMPLIFY));
    }

    public function setLiqAmplify(?float $amplify = null): void
    {
        $this->set(self::AMPLIFY, $amplify);
    }

    public const string CROSS_START_NEXT = 'liq_cross_start_next';

    public function getLiqCrossStartNext(): ?float
    {
        return Types::floatOrNull($this->get(self::CROSS_START_NEXT));
    }

    public function setLiqCrossStartNext(string|int|float $startNext = null): void
    {
        $this->set(self::CROSS_START_NEXT, self::annotationToString($startNext));
    }

    public const string FADE_IN = 'liq_fade_in';

    public function getLiqFadeIn(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_IN));
    }

    public function setLiqFadeIn(string|int|float $fadeIn = null): void
    {
        $this->set(self::FADE_IN, self::annotationToString($fadeIn));
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
        $this->set(self::CUE_IN, self::annotationToString($cueIn));
    }

    public const string CUE_OUT = 'liq_cue_out';

    public function getLiqCueOut(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_OUT));
    }

    public function setLiqCueOut(string|int|float $cueOut = null): void
    {
        $this->set(self::CUE_OUT, self::annotationToString($cueOut));
    }

    protected static function annotationToString(string|int|float $annotation = null): ?float
    {
        if (null === $annotation) {
            return null;
        }

        return Types::floatOrNull(
            Time::displayTimeToSeconds($annotation)
        );
    }

    protected static function isValidKey(string $key): bool
    {
        return str_starts_with($key, 'liq_')
            || str_starts_with($key, 'replaygain_');
    }
}
