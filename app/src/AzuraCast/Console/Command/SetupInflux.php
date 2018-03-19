<?php
namespace AzuraCast\Console\Command;

use InfluxDB\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupInflux extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:setup:influx')
            ->setDescription('Initial setup of InfluxDB.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Database $influxdb */
        $influxdb = $this->di->get(Database::class);

        // Create the database (if it doesn't exist)
        $influxdb->create();

        $output->writeln('Database created.');

        // Establish retention policies
        $retention_policies = [
           ['name' => '15s', 'duration' => '5d', 'default' => true],
           ['name' => '1h', 'duration' => '2w', 'default' => false],
           ['name' => '1d', 'duration' => 'INF', 'default' => false],
        ];

        foreach($retention_policies as $rp) {
            $rp_obj = new Database\RetentionPolicy($rp['name'], $rp['duration'], 1, $rp['default']);
            $influxdb->createRetentionPolicy($rp_obj);
        }

        $output->writeln('Retention policies created.');

        // Drop existing continuous queries.
        $cqs = $influxdb->query('SHOW CONTINUOUS QUERIES');

        foreach((array)$cqs->getPoints() as $existing_cq) {
            $influxdb->query(sprintf('DROP CONTINUOUS QUERY %s ON stations', $existing_cq['name']));
        }

        // Create continuous queries
        $downsample_retentions = ['1h', '1d'];

        foreach($downsample_retentions as $dr) {
            $cq_name = 'cq_'.$dr;
            $cq_fields = 'min(value) AS min, mean(value) AS value, max(value) AS max';

            $influxdb->query(sprintf('CREATE CONTINUOUS QUERY %s ON stations BEGIN SELECT %s INTO "%s".:MEASUREMENT FROM /.*/ GROUP BY time(%s) END', $cq_name, $cq_fields, $dr, $dr));
        }

        $output->writeln('Continuous queries created.');

        // Print debug information
        if (true || !APP_IN_PRODUCTION) {
            $rps_raw = $influxdb->query('SHOW RETENTION POLICIES');
            $rps = (array)$rps_raw->getPoints();

            $output->writeln(print_r($rps, true));

            $cqs_raw = $influxdb->query('SHOW CONTINUOUS QUERIES');
            $cqs = [];

            foreach((array)$cqs_raw->getPoints() as $cq) {
                $cqs[$cq['name']] = $cq['query'];
            }

            $output->writeln(print_r($cqs, true));
        }

        $output->writeln('InfluxDB databases created.');
    }
}