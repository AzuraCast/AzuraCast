<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Console\Application;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

final class ClearCacheAction
{
    public function __construct(
        private readonly Application $console,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        [, $resultOutput] = $this->console->runCommandWithArgs(
            'cache:clear'
        );

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->fromHere('admin:debug:index'));
    }
}
