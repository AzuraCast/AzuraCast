<?php
namespace App\Message;

class BackupMessage extends AbstractMessage
{
    /** @var string|null The absolute or relative path of the backup file. */
    public ?string $path;

    /** @var string|null The path to log output of the Backup command to. */
    public ?string $outputPath = null;

    /** @var bool Whether to exclude media, producing a much more compact backup. */
    public bool $excludeMedia = false;
}
