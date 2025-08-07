<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Container\LoggerAwareTrait;
use App\Entity\Station;
use App\Radio\Enums\BackendAdapters;
use LogicException;
use Monolog\LogRecord;
use ReflectionClass;

abstract class AbstractCommand
{
    use LoggerAwareTrait;

    public function run(
        Station $station,
        bool $asAutoDj = false,
        ?array $payload = []
    ): mixed {
        if (BackendAdapters::Liquidsoap !== $station->backend_type) {
            throw new LogicException('Station does not use Liquidsoap backend.');
        }

        $this->logger->pushProcessor(
            function (LogRecord $record) use ($station) {
                $record->extra['station'] = [
                    'id' => $station->id,
                    'name' => $station->name,
                ];
                return $record;
            }
        );

        try {
            $className = new ReflectionClass(static::class)->getShortName();
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
