<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Event\Radio\GetNextSong;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ;
use App\Sync\Runner;
use Cake\Chronos\Chronos;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class DebugController
{
    protected Logger $logger;

    protected TestHandler $testHandler;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->testHandler = new TestHandler(Logger::DEBUG, false);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        Runner $sync
    ): ResponseInterface {
        return $request->getView()->renderToResponse($response, 'admin/debug/index', [
            'sync_times' => $sync->getSyncTimes(),
            'stations' => $stationRepo->fetchSelect(),
        ]);
    }

    public function syncAction(
        ServerRequest $request,
        Response $response,
        Runner $sync,
        $type
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        switch ($type) {
            case 'long':
                $sync->syncLong(true);
                break;

            case 'medium':
                $sync->syncMedium(true);
                break;

            case 'short':
                $sync->syncShort(true);
                break;

            case 'nowplaying':
            default:
                $sync->syncNowplaying(true);
                break;
        }

        $this->logger->popHandler();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'sidebar' => null,
            'title' => __('Sync Task Output'),
            'log_records' => $this->testHandler->getRecords(),
        ]);
    }

    public function nextsongAction(
        ServerRequest $request,
        Response $response,
        AutoDJ $autoDJ
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();

        $nowString = $request->getParam('now');
        if (!empty($nowString)) {
            $stationTz = $station->getTimezone();
            $now = Chronos::parse($nowString, $stationTz);

            $this->logger->debug('Modified time for calculation.', [
                'new_time' => (string)$now,
            ]);

            Chronos::setTestNow($now);
        }

        $event = new GetNextSong($station);
        $autoDJ->calculateNextSong($event);

        Chronos::setTestNow(null);

        $this->logger->popHandler();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'sidebar' => null,
            'title' => __('Debug Output'),
            'log_records' => $this->testHandler->getRecords(),
        ]);
    }
}