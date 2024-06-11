<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Updates;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\WebUpdater;
use Psr\Http\Message\ResponseInterface;

final class PutUpdatesAction implements SingleActionInterface
{
    public function __construct(
        private readonly WebUpdater $webUpdater
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $this->webUpdater->triggerUpdate();

        return $response->withJson(Status::success());
    }
}
