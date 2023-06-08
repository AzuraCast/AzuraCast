<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Analytics;
use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Station;
use Carbon\CarbonImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class AnalyticsFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $stations = $manager->getRepository(Station::class)->findAll();

        $midnightUtc = CarbonImmutable::now('UTC')->setTime(0, 0);

        for ($i = 1; $i <= 14; $i++) {
            $day = $midnightUtc->subDays($i);

            $dayMin = 0;
            $dayMax = 0;
            $dayListeners = 0;
            $dayUnique = 0;

            foreach ($stations as $station) {
                /** @var Station $station */
                $stationListeners = random_int(10, 50);
                $stationMin = random_int(1, $stationListeners);
                $stationMax = random_int($stationListeners, 150);

                $stationUnique = random_int(1, 250);

                $dayMin = min($dayMin, $stationMin);
                $dayMax = max($dayMax, $stationMax);
                $dayListeners += $stationListeners;
                $dayUnique += $stationUnique;

                $stationPoint = new Analytics(
                    $day,
                    $station,
                    AnalyticsIntervals::Daily,
                    $stationMin,
                    $stationMax,
                    $stationListeners,
                    $stationUnique
                );
                $manager->persist($stationPoint);
            }

            $totalPoint = new Analytics(
                $day,
                null,
                AnalyticsIntervals::Daily,
                $dayMin,
                $dayMax,
                $dayListeners,
                $dayUnique
            );
            $manager->persist($totalPoint);
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            StationFixture::class,
        ];
    }
}
