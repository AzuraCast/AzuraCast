<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

final class TelnetAction
{
    public function __construct(
        private readonly Logger $logger
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id,
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if ($backend instanceof Liquidsoap) {
            $command = $request->getParam('command');

            $telnetResponse = $backend->command($station, $command);
            $this->logger->debug(
                'Telnet Command Response',
                [
                    'response' => $telnetResponse,
                ]
            );
        }

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
