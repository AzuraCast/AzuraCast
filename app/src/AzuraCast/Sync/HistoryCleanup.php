<?php
namespace AzuraCast\Sync;

use Doctrine\ORM\EntityManager;

class HistoryCleanup extends SyncAbstract
{
    public function run()
    {
        /** @var EntityManager $em */
        $em = $this->di[EntityManager::class];

        $threshold = strtotime('-1 month');

        $em->createQuery('DELETE FROM \Entity\SongHistory sh WHERE sh.timestamp_start <= :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}