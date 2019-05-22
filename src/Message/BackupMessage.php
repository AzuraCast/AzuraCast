<?php
namespace App\Message;

class BackupMessage extends AbstractMessage
{
    /** @var string|null The absolute or relative path of the backup file. */
    public $path;

    /** @var bool Whether to exclude media, producing a much more compact backup. */
    public $exclude_media = false;
}
