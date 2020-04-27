<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Runner;
use Doctrine\ORM\EntityManager;
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
            'stations' => $stationRepo->fetchArray(),
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
        EntityManager $em,
        AutoDJ $autoDJ
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();

        $em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\SongHistory sh
            WHERE sh.station = :station
            AND sh.timestamp_cued != 0
            AND sh.timestamp_start = 0')
            ->setParameter('station', $station)
            ->execute();

        $this->logger->debug('Current queue cleared.');

        $autoDJ->buildQueue($station);
        
        $this->logger->popHandler();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'sidebar' => null,
            'title' => __('Debug Output'),
            'log_records' => $this->testHandler->getRecords(),
        ]);
    }

    public function telnetAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if ($backend instanceof Liquidsoap) {
            $command = $request->getParam('command');

            $telnetResponse = $backend->command($station, $command);
            $this->logger->debug('Telnet Command Response', [
                'response' => $telnetResponse,
            ]);
        }

        $this->logger->popHandler();

        return $request->getView()->renderToResponse($response, 'system/log_view', [
            'sidebar' => null,
            'title' => __('Debug Output'),
            'log_records' => $this->testHandler->getRecords(),
        ]);
    }
}