<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationStreamerRepository;
use App\Entity\Station;
use RuntimeException;

final class DjAuthCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): array {
        if (!$station->getEnableStreamers()) {
            throw new RuntimeException('Streamers are disabled on this station.');
        }

        $user = $payload['user'] ?? '';
        $pass = $payload['password'] ?? '';

        return [
            'allow' => $this->streamerRepo->authenticate($station, $user, $pass),
        ];
    }
}
