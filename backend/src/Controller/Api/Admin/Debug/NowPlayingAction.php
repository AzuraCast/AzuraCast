<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\LogResult;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/station/{station_id}/nowplaying',
        operationId: 'adminDebugStationNowPlaying',
        summary: 'Generate the raw Now Playing data for a given station.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: LogResult::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
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

        return $response->withJson(
            LogResult::fromTestHandlerRecords($testHandler->getRecords())
        );
    }
}
