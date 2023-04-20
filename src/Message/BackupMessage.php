<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueNames;

final class BackupMessage extends AbstractUniqueMessage
{
    /** @var int|null The storage location to back up to. */
    public ?int $storageLocationId = null;

    /** @var string|null The absolute or relative path of the backup file. */
    public ?string $path = null;

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

    public function getQueue(): QueueNames
    {
        return QueueNames::LowPriority;
    }
}
