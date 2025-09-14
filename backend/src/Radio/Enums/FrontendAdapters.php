<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use App\Radio\Frontend\AbstractFrontend;
use App\Radio\Frontend\Icecast;
use App\Radio\Frontend\Rsas;
use App\Radio\Frontend\Shoutcast;
use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum FrontendAdapters: string implements AdapterTypeInterface
{
    case Icecast = 'icecast';
    case Shoutcast = 'shoutcast2';
    case Rsas = 'rsas';
    case Remote = 'remote';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getName(): string
    {
        return match ($this) {
            self::Icecast => 'Icecast 2.4',
            self::Shoutcast => 'Shoutcast DNAS 2',
            self::Rsas => 'Rocket Streaming Audio Server (RSAS)',
            self::Remote => 'Remote',
        };
    }

    /**
     * @return class-string<AbstractFrontend>|null
     */
    public function getClass(): ?string
    {
        return match ($this) {
            self::Icecast => Icecast::class,
            self::Shoutcast => Shoutcast::class,
            self::Rsas => Rsas::class,
            default => null
        };
    }

    public function isEnabled(): bool
    {
        return self::Remote !== $this;
    }

    public function supportsMounts(): bool
    {
        return match ($this) {
            self::Shoutcast, self::Icecast, self::Rsas => true,
            default => false
        };
    }

    public function supportsReload(): bool
    {
        return self::Icecast === $this
            || self::Rsas === $this;
    }

    public static function default(): self
    {
        return self::Icecast;
    }
}
