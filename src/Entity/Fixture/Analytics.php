<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use Carbon\CarbonImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class Analytics extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $stations = $manager->getRepository(Entity\Station::class)->findAll();

        $midnight_utc = CarbonImmutable::now('UTC')->setTime(0, 0);

        for ($i = 1; $i <= 14; $i++) {
            $day = $midnight_utc->subDays($i);

            $day_min = 0;
            $day_max = 0;
            $day_listeners = 0;
            $day_unique = 0;

            foreach ($stations as $station) {
                /** @var Entity\Station $station */
                $station_listeners = random_int(10, 50);
                $station_min = random_int(1, $station_listeners);
                $station_max = random_int($station_listeners, 150);

                $station_unique = random_int(1, 250);

                $day_min = min($day_min, $station_min);
                $day_max = max($day_max, $station_max);
                $day_listeners += $station_listeners;
                $day_unique += $station_unique;

                $stationPoint = new Entity\Analytics(
                    $day,
                    $station,
                    Entity\Analytics::INTERVAL_DAILY,
                    $station_min,
                    $station_max,
                    $station_listeners,
                    $station_unique
                );
                $manager->persist($stationPoint);
            }

            $totalPoint = new Entity\Analytics(
                $day,
                null,
                Entity\Analytics::INTERVAL_DAILY,
                $day_min,
                $day_max,
                $day_listeners,
                $day_unique
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
            Station::class,
        ];
    }
}
