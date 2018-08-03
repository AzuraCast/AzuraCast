<?php
namespace App\Sync\Task;

use App\Entity\SongHistory;
use Doctrine\ORM\EntityManager;

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
        $threshold = strtotime('-1 month');

        $this->em->createQuery('DELETE FROM '.SongHistory::class.' sh WHERE sh.timestamp_start <= :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}
