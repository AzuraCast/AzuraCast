<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Http\ServerRequest;
use App\Version;

final readonly class SettingsComponent implements VueComponentInterface
{
    public function __construct(
        private Version $version,
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        return [
            'releaseChannel' => $this->version->getReleaseChannelEnum()->value,
        ];
    }
}
