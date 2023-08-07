<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface;

final class NowPlayingAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly NowPlayingTask $nowPlayingTask
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        try {
            $station = $request->getStation();
            $this->nowPlayingTask->run($station);
        } finally {
            $this->logger->popHandler();
        }

        return $response->withJson([
            'logs' => $testHandler->getRecords(),
        ]);
    }
}
