<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use App\Radio\Backend\Liquidsoap;

enum BackendAdapters: string implements AdapterTypeInterface
{
    case Liquidsoap = 'liquidsoap';
    case None = 'none';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getName(): string
    {
        return match ($this) {
            self::Liquidsoap => 'Liquidsoap',
            self::None => 'Disabled',
        };
    }

    public function getClass(): ?string
    {
        return match ($this) {
            self::Liquidsoap => Liquidsoap::class,
            default => null,
        };
    }

    public function isEnabled(): bool
    {
        return self::None !== $this;
    }

    public static function default(): self
    {
        return self::Liquidsoap;
    }
}
