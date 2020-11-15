<?php

namespace App\Controller\Admin;

use App\Console\Application;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Session\Flash;
use App\Sync\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class DebugController
{
    protected Logger $logger;

    protected TestHandler $testHandler;

    protected Application $console;

    public function __construct(Logger $logger, Application $console)
    {
        $this->logger = $logger;
        $this->console = $console;

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

        $sync->runSyncTask($type, true);

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
        EntityManagerInterface $em,
        AutoDJ $autoDJ
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();

        $em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\StationQueue sq
            WHERE sq.station = :station')
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

    public function clearCacheAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        [$resultCode, $resultOutput] = $this->console->runCommandWithArgs(
            'cache:clear'
        );

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->fromHere('admin:debug:index'));
    }

    public function clearQueueAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        [$resultCode, $resultOutput] = $this->console->runCommandWithArgs(
            'queue:clear'
        );

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->fromHere('admin:debug:index'));
    }
}
