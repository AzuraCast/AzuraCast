<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\WebUpdater;
use App\Version;
use Psr\Http\Message\ResponseInterface;

final class UpdatesAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly Version $version,
        private readonly WebUpdater $webUpdater,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $this->readSettings();

        $enableWebUpdates = $this->environment->enableWebUpdater();
        if ($enableWebUpdates && !$this->webUpdater->ping()) {
            $enableWebUpdates = false;
        }

        return $response->withJson([
            'releaseChannel' => $this->version->getReleaseChannelEnum()->value,
            'initialUpdateInfo' => $settings->getUpdateResults(),
            'enableWebUpdates' => $enableWebUpdates,
        ]);
    }
}
