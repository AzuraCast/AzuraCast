<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;

final class DjOnCommand extends AbstractCommand
{
    public function __construct(
        private readonly Entity\Repository\StationStreamerRepository $streamerRepo,
    ) {
    }

    protected function doRun(
        Entity\Station $station,
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

        return $this->streamerRepo->onConnect($station, $user);
    }
}
