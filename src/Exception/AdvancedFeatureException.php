<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception;
use Psr\Log\LogLevel;
use Throwable;

class AdvancedFeatureException extends Exception
{
    // phpcs:disable Generic.Files.LineLength
    public function __construct(
        string $message = 'This feature is considered "advanced", and advanced features are not currently enabled on this installation. Update your "azuracast.env" file on your host to enable these features.',
        int $code = 0,
        Throwable $previous = null,
        string $loggerLevel = LogLevel::INFO
    ) {
        parent::__construct($message, $code, $previous, $loggerLevel);
    }
    // phpcs:enable
}
