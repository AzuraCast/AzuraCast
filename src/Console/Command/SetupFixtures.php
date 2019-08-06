<?php
namespace App\Console\Command;

use App\Entity\Station;
use Azura\Console\Command\CommandAbstract;
use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use InfluxDB\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupFixtures extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:setup:fixtures')
            ->setDescription('Install fixtures for demo / local dev.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new Loader();
        $loader->loadFromDirectory(APP_INCLUDE_ROOT.'/src/Entity/Fixture');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        // Preload sample data.

        $stations = $em->getRepository(Station::class)->findAll();

        /** @var Database $influx */
        $influx = $this->get(Database::class);

        $midnight_utc = Chronos::now('UTC')->setTime(0, 0);
        $influx_points = [];

        for($i = 1; $i <= 14; $i++) {
            $day = $midnight_utc->subDays($i)->getTimestamp();

            $day_listeners = 0;

            foreach($stations as $station) {
                /** @var Station $station */

                $station_listeners = random_int(1, 20);
                $day_listeners += $station_listeners;

                $influx_points[] = new \InfluxDB\Point(
                    'station.' . $station->getId() . '.listeners',
                    $station_listeners,
                    [],
                    ['station' => $station->getId()],
                    $day
                );
            }

            $influx_points[] = new \InfluxDB\Point(
                'station.all.listeners',
                $day_listeners,
                [],
                ['station' => 0],
                $day
            );
        }

        $influx->writePoints($influx_points, \InfluxDB\Database::PRECISION_SECONDS, '1d');

        $output->writeln('Fixtures loaded.');

        return 0;
    }
}
