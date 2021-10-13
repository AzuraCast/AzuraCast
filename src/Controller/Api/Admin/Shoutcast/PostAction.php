<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

class PostAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $scBaseDir = $environment->getParentDirectory() . '/servers/shoutcast2';

        $scTgzPath = $scBaseDir . '/sc_serv.tar.gz';
        if (is_file($scTgzPath)) {
            unlink($scTgzPath);
        }

        $flowResponse->moveTo($scTgzPath);

        $process = new Process(
            [
                'tar',
                'xvzf',
                $scTgzPath,
            ],
            $scBaseDir
        );
        $process->mustRun();

        unlink($scTgzPath);

        return $response->withJson(Entity\Api\Status::success());
    }
}
