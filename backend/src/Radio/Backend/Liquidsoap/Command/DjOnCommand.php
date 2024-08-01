<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationStreamerRepository;
use App\Entity\Station;

final class DjOnCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool|string {
        $user = $payload['user'] ?? '';

        $this->logger->notice(
            'Received "DJ connected" ping from Liquidsoap.',
            [
                'dj' => $user,
            ]
        );

        if (!$asAutoDj) {
            return false;
        }

        return $this->streamerRepo->onConnect($station, $user);
    }
}
