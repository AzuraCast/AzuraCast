<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Station;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationRestartStatus',
    type: 'object'
)]
final class StationRestartStatus
{
    #[OA\Property]
    public bool $has_started;

    #[OA\Property]
    public bool $needs_restart;

    public static function fromStation(Station $station): self
    {
        $record = new self();
        $record->has_started = $station->getHasStarted();
        $record->needs_restart = $station->getNeedsRestart();
        return $record;
    }
}
