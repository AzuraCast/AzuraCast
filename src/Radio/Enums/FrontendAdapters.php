<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Radio\Enums;

use App\Radio\Frontend\Icecast;
use App\Radio\Frontend\Remote;
use App\Radio\Frontend\Shoutcast;

enum FrontendAdapters: string implements AdapterTypeInterface
{
    case Icecast = 'icecast';
    case Shoutcast = 'shoutcast2';
    case Remote = 'remote';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getName(): string
    {
        return match ($this) {
            self::Icecast => 'Icecast 2.4',
            self::Shoutcast => 'SHOUTcast DNAS 2',
            self::Remote => 'Remote',
        };
    }

    public function getClass(): string
    {
        return match ($this) {
            self::Icecast => Icecast::class,
            self::Shoutcast => Shoutcast::class,
            self::Remote => Remote::class,
        };
    }

    public static function default(): self
    {
        return self::Icecast;
    }
}
