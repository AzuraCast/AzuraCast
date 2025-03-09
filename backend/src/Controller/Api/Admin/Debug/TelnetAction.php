<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Adapters;
use App\Utilities\Types;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/station/{station_id}/telnet',
        operationId: 'putAdminDebugTelnetCommand',
        summary: 'Manually run a Telnet command on a station backend.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class TelnetAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Adapters $adapters
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        $station = $request->getStation();
        $backend = $this->adapters->requireBackendAdapter($station);

        $command = Types::string($request->getParam('command'));

        $telnetResponse = $backend->command($station, $command);
        $this->logger->debug(
            'Telnet Command Response',
            [
                'response' => $telnetResponse,
            ]
        );

        $this->logger->popHandler();

        return $response->withJson([
            'logs' => $testHandler->getRecords(),
        ]);
    }
}
