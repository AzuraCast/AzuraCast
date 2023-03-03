<?php

declare(strict_types=1);

namespace App\Backup\Exporter;

use App\Entity\Station;

final class StationExporter
{
    

    public function __invoke(
        Station $station
    ): array {
    }
}
