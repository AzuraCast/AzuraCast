<?php
namespace App\Console\Command;

use App\Entity\Station;
use App\Settings;
use Carbon\CarbonImmutable;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use InfluxDB\Database;
use InfluxDB\Point;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupFixturesCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        ContainerInterface $di,
        Database $influx,
        Settings $settings
    ) {
        $loader = new Loader();

        // Dependency-inject the fixtures and load them.
        $fixturesDir = $settings[Settings::BASE_DIR] . '/src/Entity/Fixture';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fixturesDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            // Skip dotfiles
            if (($fileName = $file->getBasename('.php')) == $file->getBasename()) {
                continue;
            }

            $className = 'App\\Entity\\Fixture\\' . $fileName;
            $fixture = $di->get($className);

            $loader->addFixture($fixture);
        }

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        // Preload sample data.
        $stations = $em->getRepository(Station::class)->findAll();

        $midnight_utc = CarbonImmutable::now('UTC')->setTime(0, 0);
        $influx_points = [];

        for ($i = 1; $i <= 14; $i++) {
            $day = $midnight_utc->subDays($i)->getTimestamp();

            $day_listeners = 0;

            foreach ($stations as $station) {
                /** @var Station $station */

                $station_listeners = random_int(1, 20);
                $day_listeners += $station_listeners;

                $influx_points[] = new Point(
                    'station.' . $station->getId() . '.listeners',
                    (float)$station_listeners,
                    [],
                    ['station' => $station->getId()],
                    $day
                );
            }

            $influx_points[] = new Point(
                'station.all.listeners',
                (float)$day_listeners,
                [],
                ['station' => 0],
                $day
            );
        }

        $influx->writePoints($influx_points, Database::PRECISION_SECONDS, '1d');

        $io->writeln(__('Fixtures loaded.'));

        return 0;
    }
}
