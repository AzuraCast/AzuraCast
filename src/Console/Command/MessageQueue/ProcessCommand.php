<?php
namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use App\Doctrine\Messenger\ClearEntityManagerSubscriber;
use App\EventDispatcher;
use App\MessageQueue\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Worker;

class ProcessCommand extends CommandAbstract
{
    public function __invoke(
        MessageBus $messageBus,
        EventDispatcher $eventDispatcher,
        QueueManager $queueManager,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        int $runtime = 0
    ) {
        $receivers = $queueManager->getTransports();

        $eventDispatcher->addSubscriber(new ClearEntityManagerSubscriber($em));

        if ($runtime > 0) {
            $eventDispatcher->addSubscriber(new StopWorkerOnTimeLimitListener($runtime, $logger));
        }

        $worker = new Worker($receivers, $messageBus, $eventDispatcher, $logger);
        $worker->run();

        return 0;
    }
}
