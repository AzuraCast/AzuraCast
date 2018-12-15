<?php
namespace App\Sync\Task;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use App\Entity;

class HistoryCleanup extends TaskAbstract
{
    /** @var EntityManager */
    protected $em;

    /**
     * HistoryCleanup constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function run($force = false)
    {
        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $days_to_keep = (int)$settings_repo->getSetting('history_keep_days', Entity\SongHistory::DEFAULT_DAYS_TO_KEEP);

        if ($days_to_keep !== 0) {
            $threshold = (new Chronos())
                ->subDays($days_to_keep)
                ->getTimestamp();

            $this->em->createQuery('DELETE FROM ' . Entity\SongHistory::class . ' sh WHERE sh.timestamp_start != 0 AND sh.timestamp_start <= :threshold')
                ->setParameter('threshold', $threshold)
                ->execute();
        }

        // Clean up any history entries that don't represent actual played songs.
        $cleanup_threshold = time() - 43200;

        $this->em->createQuery('DELETE FROM ' . Entity\SongHistory::class . ' sh
            WHERE sh.timestamp_cued < :threshold
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0')
            ->setParameter('threshold', $cleanup_threshold)
            ->execute();
    }
}
