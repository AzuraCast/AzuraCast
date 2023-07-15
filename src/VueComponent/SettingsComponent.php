<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Entity\Settings;
use App\Http\ServerRequest;
use App\Version;

final class SettingsComponent implements VueComponentInterface
{
    public function __construct(
        private readonly Version $version,
    ) {
    }

    public function getProps(ServerRequest $request): array
    {
        $router = $request->getRouter();

        return [
            'apiUrl' => $router->named('api:admin:settings', [
                'group' => Settings::GROUP_GENERAL,
            ]),
            'testMessageUrl' => $router->named('api:admin:send-test-message'),
            'acmeUrl' => $router->named('api:admin:acme'),
            'releaseChannel' => $this->version->getReleaseChannelEnum()->value,
        ];
    }
}
