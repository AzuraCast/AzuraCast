<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationStreamerRepository;
use App\Entity\Station;

final class DjOffCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        $this->logger->notice('Received "DJ disconnected" ping from Liquidsoap.');

        if (!$asAutoDj) {
            return false;
        }

        return $this->streamerRepo->onDisconnect($station);
    }
}
