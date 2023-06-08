<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Container\LoggerAwareTrait;
use App\Entity\Station;
use App\Radio\Enums\BackendAdapters;
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
    ): string {
        $this->logger->pushProcessor(
            function (LogRecord $record) use ($station) {
                $record->extra['station'] = [
                    'id' => $station->getId(),
                    'name' => $station->getName(),
                ];
                return $record;
            }
        );

        $className = (new ReflectionClass(static::class))->getShortName();
        $this->logger->debug(
            sprintf('Running Internal Command %s', $className),
            [
                'asAutoDj' => $asAutoDj,
                'payload' => $payload,
            ]
        );

        try {
            if (BackendAdapters::Liquidsoap !== $station->getBackendType()) {
                $this->logger->error('Station does not use Liquidsoap backend.');
                return 'false';
            }

            $result = $this->doRun($station, $asAutoDj, $payload ?? []);

            if (true === $result) {
                return 'true';
            }
            if (false === $result) {
                return 'false';
            }

            return (string)$result;
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Error with Internal Command %s: %s',
                    $className,
                    $e->getMessage()
                ),
                [
                    'exception' => $e,
                ]
            );

            return 'false';
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
