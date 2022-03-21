<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use App\Flysystem\StationFilesystems;
use Monolog\Logger;

class DjOnCommand extends AbstractCommand
{
    public function __construct(
        Logger $logger,
        protected Entity\Repository\StationStreamerRepository $streamerRepo,
    ) {
        parent::__construct($logger);
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

        $resp = $this->streamerRepo->onConnect($station, $user);

        if (is_string($resp)) {
            return (new StationFilesystems($station))
                ->getTempFilesystem()
                ->getLocalPath($resp);
        }

        return $resp;
    }
}
