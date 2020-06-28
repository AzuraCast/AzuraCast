<?php
namespace App\Console\Command;

use App\EventDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Worker;

class ProcessMessageQueueCommand extends CommandAbstract
{
    public function __invoke(
        MessageBus $messageBus,
        EventDispatcher $eventDispatcher,
        RedisTransport $redisTransport,
        LoggerInterface $logger,
        int $runtime = 0
    ) {
        $receivers = [
            RedisTransport::class => $redisTransport,
        ];

        if ($runtime > 0) {
            $eventDispatcher->addSubscriber(new StopWorkerOnTimeLimitListener($runtime, $logger));
        }

        $worker = new Worker($receivers, $messageBus, $eventDispatcher, $logger);
        $worker->run();

        return 0;
    }
}
