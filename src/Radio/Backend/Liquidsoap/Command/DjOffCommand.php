<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;

final class DjOffCommand extends AbstractCommand
{
    public function __construct(
        private readonly Entity\Repository\StationStreamerRepository $streamerRepo,
    ) {
    }

    protected function doRun(
        Entity\Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        $this->logger->notice('Received "DJ disconnected" ping from Liquidsoap.');

        return $this->streamerRepo->onDisconnect($station);
    }
}
