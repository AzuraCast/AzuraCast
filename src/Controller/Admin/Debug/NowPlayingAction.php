<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

final class NowPlayingAction
{
    public function __construct(
        private readonly Logger $logger,
        private readonly NowPlayingTask $nowPlayingTask
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        try {
            $station = $request->getStation();
            $this->nowPlayingTask->run($station);
        } finally {
            $this->logger->popHandler();
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $testHandler->getRecords(),
            ]
        );
    }
}
