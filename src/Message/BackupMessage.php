<?php

namespace App\Message;

use App\MessageQueue\QueueManager;

class BackupMessage extends AbstractUniqueMessage
{
    /** @var string|null The absolute or relative path of the backup file. */
    public ?string $path;

    /** @var string|null The path to log output of the Backup command to. */
    public ?string $outputPath = null;

    /** @var bool Whether to exclude media, producing a much more compact backup. */
    public bool $excludeMedia = false;

    public function getIdentifier(): string
    {
        // The system should only ever be running one backup task at a given time.
        return 'BackupMessage';
    }

    public function getTtl(): ?float
    {
        return 86400;
    }

    public function getQueue(): string
    {
        return QueueManager::QUEUE_LOW_PRIORITY;
    }
}
