<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use App\Flysystem\StationFilesystems;
use Monolog\Logger;
use RuntimeException;

final class CopyCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationFilesystems $stationFilesystems,
        Logger $logger
    ) {
        parent::__construct($logger);
    }

    protected function doRun(Entity\Station $station, bool $asAutoDj = false, array $payload = []): string
    {
        if (empty($payload['uri'])) {
            throw new RuntimeException('No URI provided.');
        }

        $uri = $payload['uri'];

        return $this->stationFilesystems->getMediaFilesystem($station)
            ->getLocalPath($uri);
    }
}
