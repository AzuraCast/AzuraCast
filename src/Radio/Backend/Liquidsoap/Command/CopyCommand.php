<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use App\Flysystem\StationFilesystems;
use RuntimeException;

final class CopyCommand extends AbstractCommand
{
    protected function doRun(Entity\Station $station, bool $asAutoDj = false, array $payload = []): string
    {
        if (empty($payload['uri'])) {
            throw new RuntimeException('No URI provided.');
        }

        $uri = $payload['uri'];

        return (new StationFilesystems($station))
            ->getMediaFilesystem()
            ->getLocalPath($uri);
    }
}
