<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use Monolog\Handler\RotatingFileHandler;

abstract class AbstractSyncCommand extends CommandAbstract
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;

    protected function logToExtraFile(string $extraFilePath): void
    {
        $logExtraFile = new RotatingFileHandler(
            $this->environment->getTempDirectory() . '/' . $extraFilePath,
            5,
            $this->environment->getLogLevel(),
            true
        );
        $this->logger->pushHandler($logExtraFile);
    }
}
