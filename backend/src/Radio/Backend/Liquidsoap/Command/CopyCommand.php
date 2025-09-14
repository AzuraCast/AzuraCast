<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Station;
use App\Flysystem\StationFilesystems;
use InvalidArgumentException;

final class CopyCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationFilesystems $stationFilesystems,
    ) {
    }

    protected function doRun(Station $station, bool $asAutoDj = false, array $payload = []): array
    {
        if (empty($payload['uri'])) {
            throw new InvalidArgumentException('No URI provided.');
        }

        $uri = $payload['uri'];

        $mediaFs = $this->stationFilesystems->getMediaFilesystem($station);
        $localPath = $mediaFs->getLocalPath($uri);

        return [
            'uri' => $localPath,
            'isTemp' => !$mediaFs->isLocal(),
        ];
    }
}
