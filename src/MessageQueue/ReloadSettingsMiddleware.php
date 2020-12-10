<?php

namespace App\MessageQueue;

use App\Entity\Repository\SettingsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

class ReloadSettingsMiddleware implements EventSubscriberInterface
{
    protected SettingsRepository $settingsRepo;

    public function __construct(SettingsRepository $settingsRepo)
    {
        $this->settingsRepo = $settingsRepo;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkerMessageReceivedEvent::class => 'resetSettings',
        ];
    }

    public function resetSettings(WorkerMessageReceivedEvent $event): void
    {
        $this->settingsRepo->clearSettingsInstance();
    }
}
