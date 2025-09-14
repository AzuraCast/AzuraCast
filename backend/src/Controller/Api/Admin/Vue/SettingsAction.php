<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Vue\SettingsProps;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;

final readonly class SettingsAction implements SingleActionInterface
{
    public function __construct(
        private Version $version,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            new SettingsProps(
                releaseChannel: $this->version->getReleaseChannelEnum()->value
            )
        );
    }
}
