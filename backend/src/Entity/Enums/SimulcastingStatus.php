<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum SimulcastingStatus: string
{
    case Stopped = 'stopped';
    case Running = 'running';
    case Error = 'error';
    case Starting = 'starting';
    case Stopping = 'stopping';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stopped => 'Stopped',
            self::Running => 'Running',
            self::Error => 'Error',
            self::Starting => 'Starting',
            self::Stopping => 'Stopping',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Stopped => 'secondary',
            self::Running => 'success',
            self::Error => 'danger',
            self::Starting => 'warning',
            self::Stopping => 'warning',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Running, self::Starting, self::Stopping]);
    }

    public function canStart(): bool
    {
        return in_array($this, [self::Stopped, self::Error]);
    }

    public function canStop(): bool
    {
        return in_array($this, [self::Running, self::Starting]);
    }
}
