<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Station;
use App\Entity\StationMediaComputedAutoCue;
use App\Radio\Backend\Liquidsoap\AutoCueCacheKey;
use App\Utilities\Types;
use Doctrine\DBAL\Types\Types as DbalTypes;

final class SaveCacheCommand extends AbstractCommand
{
    use EntityManagerAwareTrait;

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        if (!$asAutoDj) {
            return false;
        }

        $cacheKey = AutoCueCacheKey::tryFromString(Types::string($payload['cache_key'] ?? null));
        $computedValues = Types::arrayOrNull($payload['data'] ?? null);

        if ($cacheKey === null || empty($computedValues)) {
            return false;
        }

        $this->saveComputedAutoCue($station, $cacheKey, $computedValues);

        return true;
    }

    /**
     * @param array<array-key, mixed> $computedValues
     */
    private function saveComputedAutoCue(
        Station $station,
        AutoCueCacheKey $cacheKey,
        array $computedValues
    ): void {
        $computedAutoCue = StationMediaComputedAutoCue::fromComputedValues($cacheKey->mtime, $computedValues);

        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.computed_auto_cue_raw = :computedAutoCue
                WHERE sm.id = :id
                AND sm.mtime = :mtime
                AND sm.storage_location = :storageLocation
            DQL
        )->setParameter('computedAutoCue', $computedAutoCue->toArray(), DbalTypes::JSON)
            ->setParameter('id', $cacheKey->mediaId)
            ->setParameter('mtime', $cacheKey->mtime)
            ->setParameter('storageLocation', $station->media_storage_location)
            ->execute();
    }
}
