<?php
namespace App\Console\Command;

use App;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;

class UptimeWaitCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        ?string $service = 'database'
    ) {
        $attempts = 0;
        $total_attempts = 5;
        $sleep_time = 5;

        $service_name = strtolower($service);
        switch ($service_name) {
            case 'influxdb':
            case 'influx':
                break;

            case 'database':
            case 'mariadb':
            case 'mysql':
            default:
                $conn = $em->getConnection();

                while ($attempts <= $total_attempts) {
                    $attempts++;

                    try {
                        $conn->connect();
                        $io->writeln('Successfully connected');
                        return 0;
                    } catch (Exception $e) {
                        $io->writeln($e->getMessage());
                        sleep($sleep_time);
                        continue;
                    }
                }

                return 1;
                break;
        }

        throw new InvalidArgumentException('Invalid service specified.');
    }
}
