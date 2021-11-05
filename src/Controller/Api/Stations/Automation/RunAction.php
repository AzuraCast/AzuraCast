<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Automation;

use App\Controller\Api\Admin\StationsController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Sync\Task\RunAutomatedAssignmentTask;
use Psr\Http\Message\ResponseInterface;

class RunAction extends StationsController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        RunAutomatedAssignmentTask $syncTask
    ): ResponseInterface {
        $station = $request->getStation();

        try {
            $syncTask->runStation($station, true);
            return $response->withJson(Entity\Api\Status::success());
        } catch (\Throwable $e) {
            return $response->withStatus(400)->withJson(Entity\Api\Error::fromException($e));
        }
    }
}
