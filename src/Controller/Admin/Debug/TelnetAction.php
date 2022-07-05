<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

final class TelnetAction
{
    public function __construct(
        private readonly Logger $logger,
        private readonly Adapters $adapters
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        $station = $request->getStation();
        $backend = $this->adapters->getBackendAdapter($station);

        if (null === $backend) {
            throw new StationUnsupportedException();
        }

        $command = $request->getParam('command');

        $telnetResponse = $backend->command($station, $command);
        $this->logger->debug(
            'Telnet Command Response',
            [
                'response' => $telnetResponse,
            ]
        );

        $this->logger->popHandler();

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
