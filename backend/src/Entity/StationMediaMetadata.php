<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Time;
use App\Utilities\Types;

class StationMediaMetadata extends AbstractStationConfiguration
{
    protected function set(string $key, mixed $value): static
    {
        if (!self::isValidKey($key)) {
            return $this;
        }

        return parent::set($key, self::annotationToString($value));
    }

    public function toArray(): array
    {
        return array_filter(
            $this->data,
            [$this, 'isValidKey'],
            ARRAY_FILTER_USE_KEY
        );
    }

    public const string AMPLIFY = 'liq_amplify';

    public function getAmplify(): ?float
    {
        return Types::floatOrNull($this->get(self::AMPLIFY));
    }

    public function setAmplify(?float $amplify = null): void
    {
        $this->set(self::AMPLIFY, $amplify);
    }

    public const string CROSS_START_NEXT = 'liq_cross_start_next';

    public function getFadeStartNext(): ?float
    {
        return Types::floatOrNull($this->get(self::CROSS_START_NEXT));
    }

    public function setFadeStartNext(?float $fade_start_next = null): void
    {
        $this->set(self::CROSS_START_NEXT, $fade_start_next);
    }

    public const string FADE_IN = 'liq_fade_in';

    public function getFadeIn(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_IN));
    }

    public function setFadeIn(string|int|float $fadeIn = null): void
    {
        $this->set(self::FADE_IN, $fadeIn);
    }

    public const string FADE_OUT = 'liq_fade_out';

    public function getFadeOut(): ?float
    {
        return Types::floatOrNull($this->get(self::FADE_OUT));
    }

    public function setFadeOut(string|int|float $fadeOut = null): void
    {
        $this->set(self::FADE_OUT, $fadeOut);
    }

    public const string CUE_IN = 'liq_cue_in';

    public function getCueIn(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_IN));
    }

    public function setCueIn(string|int|float $cueIn = null): void
    {
        $this->set(self::CUE_IN, $cueIn);
    }

    public const string CUE_OUT = 'liq_cue_out';

    public function getCueOut(): ?float
    {
        return Types::floatOrNull($this->get(self::CUE_OUT));
    }

    public function setCueOut(string|int|float $cueOut = null): void
    {
        $this->set(self::CUE_OUT, $cueOut);
    }

    protected static function annotationToString(mixed $annotation = null): ?string
    {
        if (null === $annotation) {
            return null;
        }

        if (!is_string($annotation) || !is_numeric($annotation)) {
            return null;
        }

        return Types::stringOrNull(
            Time::displayTimeToSeconds($annotation)
        );
    }

    protected static function isValidKey(string $key): bool
    {
        return str_starts_with($key, 'liq_')
            || str_starts_with($key, 'replaygain_');
    }
}
