<?php

namespace App\Message;

use App\Environment;
use App\MessageQueue\QueueManager;

class RunSyncTaskMessage extends AbstractUniqueMessage
{
    public string $type;

    /** @var string|null The path to log output of the Backup command to. */
    public ?string $outputPath = null;

    public function getIdentifier(): string
    {
        return 'SyncTask_' . $this->type;
    }

    public function getTtl(): ?float
    {
        return Environment::getInstance()->getSyncLongExecutionTime();
    }

    public function getQueue(): string
    {
        return QueueManager::QUEUE_HIGH_PRIORITY;
    }
}
