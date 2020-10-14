<?php

namespace App\Doctrine\Messenger;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

class ClearEntityManagerSubscriber implements EventSubscriberInterface
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
            WorkerMessageFailedEvent::class => 'onWorkerMessageFailed',
        ];
    }

    public function onWorkerMessageHandled(): void
    {
        $this->clearEntityManagers();
    }

    public function onWorkerMessageFailed(): void
    {
        $this->clearEntityManagers();
    }

    protected function clearEntityManagers(): void
    {
        $this->em->clear();
        gc_collect_cycles();
    }
}
