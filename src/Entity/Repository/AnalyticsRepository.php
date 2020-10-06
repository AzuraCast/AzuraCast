<?php
namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use DateTimeInterface;

class AnalyticsRepository extends Repository
{
    public function findForStationAfterTime(
        Entity\Station $station,
        DateTimeInterface $threshold,
        string $type = Entity\Analytics::INTERVAL_DAILY
    ): array {
        return $this->em->createQuery(/** @lang DQL */ 'SELECT a
            FROM App\Entity\Analytics a
            WHERE a.station = :station
            AND a.type = :type
            AND a.timestamp >= :threshold')
            ->setParameter('station', $station)
            ->setParameter('type', $type)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();
    }

}