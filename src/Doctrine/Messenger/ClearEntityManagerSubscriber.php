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

    public function onWorkerMessageHandled(): void
    {
        $this->clearEntityManagers();
    }

    public function onWorkerMessageFailed(): void
    {
        $this->clearEntityManagers();
    }

    public static function getSubscribedEvents()
    {
        yield WorkerMessageHandledEvent::class => 'onWorkerMessageHandled';
        yield WorkerMessageFailedEvent::class => 'onWorkerMessageFailed';
    }

    protected function clearEntityManagers(): void
    {
        $this->em->clear();
        gc_collect_cycles();
    }
}