<?php
namespace App\Sync\Task;

use App\Entity;
use Carbon\CarbonImmutable;

class HistoryCleanup extends AbstractTask
{
    public function run(bool $force = false): void
    {
        $days_to_keep = (int)$this->settingsRepo->getSetting(Entity\Settings::HISTORY_KEEP_DAYS,
            Entity\SongHistory::DEFAULT_DAYS_TO_KEEP);

        if ($days_to_keep !== 0) {
            $threshold = (new CarbonImmutable())
                ->subDays($days_to_keep)
                ->getTimestamp();

            $this->em->createQuery(/** @lang DQL */ 'DELETE 
                FROM App\Entity\SongHistory sh 
                WHERE sh.timestamp_start != 0 
                AND sh.timestamp_start <= :threshold')
                ->setParameter('threshold', $threshold)
                ->execute();
        }
    }
}
