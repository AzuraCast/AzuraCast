<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PostAction
{
    public function __construct(
        private readonly Environment $environment,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        if ('x86_64' !== php_uname('m')) {
            throw new RuntimeException('Shoutcast cannot be installed on non-X86_64 systems.');
        }

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $scBaseDir = $this->environment->getParentDirectory() . '/servers/shoutcast2';

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
