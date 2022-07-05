<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use Monolog\Logger;

final class DjOffCommand extends AbstractCommand
{
    public function __construct(
        Logger $logger,
        private readonly Entity\Repository\StationStreamerRepository $streamerRepo,
    ) {
        parent::__construct($logger);
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
