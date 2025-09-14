<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Analytics;
use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Station;
use App\Utilities\DateRange;
use App\Utilities\Time;
use Carbon\CarbonImmutable;

/**
 * @extends Repository<Analytics>
 */
final class AnalyticsRepository extends Repository
{
    protected string $entityClass = Analytics::class;

    /**
     * @return mixed[]
     */
    public function findForStationInRange(
        Station $station,
        DateRange $dateRange,
        AnalyticsIntervals $type = AnalyticsIntervals::Daily
    ): array {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT a FROM App\Entity\Analytics a
                WHERE a.station = :station AND a.type = :type AND a.moment BETWEEN :start AND :end
            DQL
        )->setParameter('station', $station)
            ->setParameter('type', $type)
            ->setParameter('start', $dateRange->start)
            ->setParameter('end', $dateRange->end)
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
        $hourlyRetention = CarbonImmutable::now()->subDays(14);

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\Analytics a
                WHERE a.type = :type AND a.moment <= :threshold
            DQL
        )->setParameter('type', AnalyticsIntervals::Hourly)
            ->setParameter('threshold', $hourlyRetention)
            ->execute();
    }

    public function getLatestDayRecord(): ?CarbonImmutable
    {
        $row = $this->em->createQuery(
            <<<'DQL'
            SELECT a FROM App\Entity\Analytics a
            WHERE a.station IS NULL AND a.type = :day
            ORDER BY a.moment DESC
            DQL
        )->setParameter('day', AnalyticsIntervals::Daily)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return ($row instanceof Analytics)
            ? Time::toUtcCarbonImmutable($row->moment)
            : null;
    }
}
