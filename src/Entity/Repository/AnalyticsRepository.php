<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Utilities\DateRange;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * @extends Repository<Entity\Analytics>
 */
final class AnalyticsRepository extends Repository
{
    /**
     * @return mixed[]
     */
    public function findForStationInRange(
        Entity\Station $station,
        DateRange $dateRange,
        Entity\Enums\AnalyticsIntervals $type = Entity\Enums\AnalyticsIntervals::Daily
    ): array {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT a FROM App\Entity\Analytics a
                WHERE a.station = :station AND a.type = :type AND a.moment BETWEEN :start AND :end
            DQL
        )->setParameter('station', $station)
            ->setParameter('type', $type)
            ->setParameter('start', $dateRange->getStart())
            ->setParameter('end', $dateRange->getEnd())
            ->getArrayResult();
    }

    public function clearAll(): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\Analytics a
            DQL
        )->execute();
    }

    public function cleanup(): void
    {
        $hourlyRetention = CarbonImmutable::now()
            ->subDays(14);

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\Analytics a
                WHERE a.type = :type AND a.moment <= :threshold
            DQL
        )->setParameter('type', Entity\Enums\AnalyticsIntervals::Hourly)
            ->setParameter('threshold', $hourlyRetention)
            ->execute();
    }

    public function clearSingleMetric(
        Entity\Enums\AnalyticsIntervals $type,
        CarbonInterface $moment,
        ?Entity\Station $station = null
    ): void {
        if (null === $station) {
            $this->em->createQuery(
                <<<'DQL'
                    DELETE FROM App\Entity\Analytics a
                    WHERE a.station IS NULL AND a.type = :type AND a.moment = :moment
                DQL
            )->setParameter('type', $type)
                ->setParameter('moment', $moment)
                ->execute();
        } else {
            $this->em->createQuery(
                <<<'DQL'
                    DELETE FROM App\Entity\Analytics a
                    WHERE a.station = :station AND a.type = :type AND a.moment = :moment
                DQL
            )->setParameter('station', $station)
                ->setParameter('type', $type)
                ->setParameter('moment', $moment)
                ->execute();
        }
    }
}
