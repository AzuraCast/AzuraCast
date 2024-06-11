<?php

declare(strict_types=1);

namespace App\Message;

use App\Environment;
use Monolog\Level;

final class TestWebhookMessage extends AbstractUniqueMessage
{
    public int $webhookId;

    /** @var string|null The path to log output of the Backup command to. */
    public ?string $outputPath = null;

    /** @var value-of<Level::VALUES> */
    public int $logLevel = Level::Info->value;

    public function getIdentifier(): string
    {
        return 'TestWebHook_' . $this->webhookId;
    }

    public function getTtl(): ?float
    {
        return Environment::getInstance()->getSyncLongExecutionTime();
    }
}
