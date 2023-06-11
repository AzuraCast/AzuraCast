<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Console\Application;
use App\Controller\SingleActionInterface;
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

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->success($resultOutput);

        return $response->withRedirect($request->getRouter()->fromHere('admin:debug:index'));
    }
}
