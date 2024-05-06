<?php

declare(strict_types=1);

namespace App\Message;

final class GenerateAcmeCertificate extends AbstractMessage
{
    /** @var string|null The path to log output of the command to. */
    public ?string $outputPath = null;

    public bool $force = false;
}
