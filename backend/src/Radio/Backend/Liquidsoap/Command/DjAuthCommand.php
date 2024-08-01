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
    ): bool {
        if (!$station->getEnableStreamers()) {
            throw new RuntimeException('Attempted DJ authentication when streamers are disabled on this station.');
        }

        $user = $payload['user'] ?? '';
        $pass = $payload['password'] ?? '';

        // Allow connections using the exact broadcast source password.
        $sourcePw = $station->getFrontendConfig()->getSourcePassword();
        if (!empty($sourcePw) && strcmp($sourcePw, $pass) === 0) {
            return true;
        }

        return $this->streamerRepo->authenticate($station, $user, $pass);
    }
}
