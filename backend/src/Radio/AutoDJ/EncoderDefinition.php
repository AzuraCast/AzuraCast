<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;

final class EncoderDefinition
{
    public function __construct(
        public ?StreamFormats $format = null,
        public ?int $bitrate = null,
        public AdapterTypeInterface $adapterType = BackendAdapters::None,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $mount = null,
        public ?StreamProtocols $protocol = null,
        public ?string $username = null,
        public ?string $password = null,
        public bool $isPublic = false
    ) {
    }

    public bool $isShoutcast {
        get => in_array(
            $this->adapterType,
            [
                FrontendAdapters::Shoutcast,
                RemoteAdapters::Shoutcast1,
                RemoteAdapters::Shoutcast2,
            ],
            true
        );
    }
}
