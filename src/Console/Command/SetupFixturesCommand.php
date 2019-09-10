<?php
namespace App\Console\Command;

use App\Entity\Station;
use Azura\Console\Command\CommandAbstract;
use Azura\Settings;
use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use InfluxDB\Database;
use InfluxDB\Point;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupFixturesCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Database $influx,
        Settings $settings
    ) {
        $loader = new Loader();
        $loader->loadFromDirectory($settings[Settings::BASE_DIR] . '/src/Entity/Fixture');

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        // Preload sample data.
        $stations = $em->getRepository(Station::class)->findAll();

        $midnight_utc = Chronos::now('UTC')->setTime(0, 0);
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
                    $station_listeners,
                    [],
                    ['station' => $station->getId()],
                    $day
                );
            }

            $influx_points[] = new Point(
                'station.all.listeners',
                $day_listeners,
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
