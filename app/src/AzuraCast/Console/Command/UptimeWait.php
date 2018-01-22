<?php
namespace AzuraCast\Console\Command;

use AzuraCast;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UptimeWait extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:uptime-wait')
            ->setDescription('Wait until a service is online and accepting connections before continuing.')
            ->addArgument(
                'service',
                InputArgument::OPTIONAL,
                'The service to check (database, influxdb).',
                'database'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $attempts = 0;
        $total_attempts = 5;
        $sleep_time = 5;

        $service_name = strtolower($input->getArgument('service'));
        switch ($service_name) {
            case "influxdb":
            case "influx":


                break;

            case "database":
            case "mariadb":
            case "mysql":
            default:

                /** @var EntityManager $em */
                $em = $this->di[EntityManager::class];

                $conn = $em->getConnection();

                while($attempts <= $total_attempts) {
                    $attempts++;

                    try {
                        $conn->connect();
                        $output->writeln('Successfully connected');
                        exit(0);
                    } catch(\Exception $e) {
                        $output->writeln($e->getMessage());
                        sleep($sleep_time);
                        continue;
                    }
                }

                exit(1);

                break;
        }
    }
}