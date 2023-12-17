<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface;

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

        $command = $request->getParam('command');

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
