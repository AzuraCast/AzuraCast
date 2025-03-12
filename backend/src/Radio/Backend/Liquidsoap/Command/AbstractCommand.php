<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Container\LoggerAwareTrait;
use App\Entity\Station;
use App\Radio\Enums\BackendAdapters;
use App\Utilities\Types;
use LogicException;
use Monolog\LogRecord;
use ReflectionClass;
use Throwable;

abstract class AbstractCommand
{
    use LoggerAwareTrait;

    public function run(
        Station $station,
        bool $asAutoDj = false,
        ?array $payload = []
    ): mixed {
        if (BackendAdapters::Liquidsoap !== $station->getBackendType()) {
            throw new LogicException('Station does not use Liquidsoap backend.');
        }

        $this->logger->pushProcessor(
            function (LogRecord $record) use ($station) {
                $record->extra['station'] = [
                    'id' => $station->getId(),
                    'name' => $station->getName(),
                ];
                return $record;
            }
        );

        try {
            $className = (new ReflectionClass(static::class))->getShortName();
            $this->logger->debug(
                sprintf('Running Internal Command %s', $className),
                [
                    'asAutoDj' => $asAutoDj,
                    'payload' => $payload,
                ]
            );

            return $this->doRun($station, $asAutoDj, $payload ?? []);
        } finally {
            $this->logger->popProcessor();
        }
    }

    abstract protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): mixed;
}
