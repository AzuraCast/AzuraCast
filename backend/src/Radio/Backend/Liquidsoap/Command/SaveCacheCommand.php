<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Cache\AutoCueCache;
use App\Entity\Station;
use App\Utilities\Types;

final class SaveCacheCommand extends AbstractCommand
{
    public function __construct(
        private readonly AutoCueCache $autoCueCache
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        if (!$asAutoDj) {
            return false;
        }

        $cacheKey = $payload['cache_key'] ?? null;
        $data = Types::arrayOrNull($payload['data'] ?? null);

        if (empty($cacheKey) || empty($data)) {
            return false;
        }

        $this->autoCueCache->setForCacheKey($cacheKey, $data);

        return true;
    }
}
