<?php

namespace App\MessageQueue;

use App\Entity\Repository\SettingsTableRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

class ReloadSettingsMiddleware implements EventSubscriberInterface
{
    protected SettingsTableRepository $settingsTableRepo;

    public function __construct(SettingsTableRepository $settingsTableRepo)
    {
        $this->settingsTableRepo = $settingsTableRepo;
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
        $this->settingsTableRepo->updateSettings();
    }
}
