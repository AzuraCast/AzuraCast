<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Console\Application;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ClearCacheAction implements SingleActionInterface
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        [, $resultOutput] = $this->console->runCommandWithArgs(
            'cache:clear'
        );

        // TODO Flash an update to ensure the session is recreated.
        // $request->getFlash()->success($resultOutput);

        return $response->withJson(Status::updated());
    }
}
