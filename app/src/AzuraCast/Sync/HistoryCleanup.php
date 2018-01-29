<?php
namespace AzuraCast\Sync;

use Doctrine\ORM\EntityManager;

class HistoryCleanup extends SyncAbstract
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

    public function run()
    {
        $threshold = strtotime('-1 month');

        $this->em->createQuery('DELETE FROM \Entity\SongHistory sh WHERE sh.timestamp_start <= :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}