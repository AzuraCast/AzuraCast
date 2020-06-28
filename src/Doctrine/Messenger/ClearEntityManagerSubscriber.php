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

    public function onWorkerMessageHandled()
    {
        $this->clearEntityManagers();
    }

    public function onWorkerMessageFailed()
    {
        $this->clearEntityManagers();
    }

    public static function getSubscribedEvents()
    {
        yield WorkerMessageHandledEvent::class => 'onWorkerMessageHandled';
        yield WorkerMessageFailedEvent::class => 'onWorkerMessageFailed';
    }

    protected function clearEntityManagers()
    {
        $this->em->clear();
        gc_collect_cycles();
    }
}