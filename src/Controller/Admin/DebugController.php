<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Console\Application;
use App\Controller\AbstractLogViewerController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\RunSyncTaskMessage;
use App\MessageQueue\AbstractQueueManager;
use App\MessageQueue\QueueManagerInterface;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Session\Flash;
use App\Sync\Runner;
use App\Utilities\File;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class DebugController extends AbstractLogViewerController
{
    protected TestHandler $testHandler;

    public function __construct(
        protected Logger $logger,
        protected Application $console,
        protected MessageBus $messageBus
    ) {
        $this->testHandler = new TestHandler(Logger::DEBUG, false);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationRepository $stationRepo,
        Runner $sync,
        QueueManagerInterface $queueManager
    ): ResponseInterface {
        $queues = AbstractQueueManager::getAllQueues();

        $queueTotals = [];
        foreach ($queues as $queue) {
            $queueTotals[$queue] = $queueManager->getQueueCount($queue);
        }

        return $request->getView()->renderToResponse(
            $response,
            'admin/debug/index',
            [
                'sync_times' => $sync->getSyncTimes(),
                'queue_totals' => $queueTotals,
                'stations' => $stationRepo->fetchArray(),
            ]
        );
    }

    public function syncAction(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $tempFile = File::generateTempPath('sync_task_' . $type . '.log');

        $message = new RunSyncTaskMessage();
        $message->type = $type;
        $message->outputPath = $tempFile;

        $this->messageBus->dispatch($message);

        return $request->getView()->renderToResponse(
            $response,
            'admin/debug/sync',
            [
                'title' => __('Run Synchronized Task'),
                'outputLog' => basename($tempFile),
            ]
        );
    }

    public function logAction(
        ServerRequest $request,
        Response $response,
        string $path
    ): ResponseInterface {
        $logPath = File::validateTempPath($path);

        return $this->view($request, $response, $logPath, true);
    }

    public function nextsongAction(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        AutoDJ $autoDJ
    ): ResponseInterface {
        $this->logger->pushHandler($this->testHandler);

        $station = $request->getStation();

        $em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq WHERE sq.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->logger->debug('Current queue cleared.');

        $autoDJ->buildQueue($station, true);

        $this->logger->popHandler();

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
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
                'log_records' => $this->testHandler->getRecords(),
            ]
        );
    }

    public function clearCacheAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        [, $resultOutput] = $this->console->runCommandWithArgs(
            'cache:clear'
        );

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect((string)$request->getRouter()->fromHere('admin:debug:index'));
    }

    public function clearQueueAction(
        ServerRequest $request,
        Response $response,
        ?string $queue = null
    ): ResponseInterface {
        $args = [];
        if (!empty($queue)) {
            $args['queue'] = $queue;
        }

        [, $resultOutput] = $this->console->runCommandWithArgs('queue:clear', $args);

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->addMessage($resultOutput, Flash::SUCCESS);

        return $response->withRedirect((string)$request->getRouter()->fromHere('admin:debug:index'));
    }
}
