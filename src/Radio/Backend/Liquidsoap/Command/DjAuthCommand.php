<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use Monolog\Logger;
use RuntimeException;

final class DjAuthCommand extends AbstractCommand
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
