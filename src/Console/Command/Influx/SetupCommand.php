<?php
namespace App\Console\Command\Influx;

use App\Console\Command\CommandAbstract;
use App\Settings;
use InfluxDB\Database;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Database $influxdb,
        Settings $settings
    ) {
        $db_name = $influxdb->getName();

        // Create the database (if it doesn't exist)
        $influxdb->create();

        $io->writeln(__('Database created.'));

        // Establish retention policies
        $retention_policies = [
            ['name' => '15s', 'duration' => '5d', 'default' => true],
            ['name' => '1h', 'duration' => '2w', 'default' => false],
            ['name' => '1d', 'duration' => 'INF', 'default' => false],
        ];

        $all_rps_raw = $influxdb->listRetentionPolicies();
        $existing_rps = [];

        foreach ($all_rps_raw as $rp) {
            $existing_rps[$rp['name']] = $rp;
        }

        foreach ($retention_policies as $rp) {
            $rp_obj = new Database\RetentionPolicy($rp['name'], $rp['duration'], 1, $rp['default']);

            if (isset($existing_rps[$rp['name']])) {
                $influxdb->alterRetentionPolicy($rp_obj);
                unset($existing_rps[$rp['name']]);
            } else {
                $influxdb->createRetentionPolicy($rp_obj);
            }
        }

        // Remove any remaining retention policies that aren't defined here
        if (!empty($existing_rps)) {
            foreach ($existing_rps as $rp_name => $rp_info) {
                $influxdb->query(sprintf('DROP RETENTION POLICY %s ON %s', $rp_name, $db_name));
            }
        }

        $io->writeln(__('Retention policies updated.'));

        // Drop existing continuous queries.
        $cqs = $influxdb->query('SHOW CONTINUOUS QUERIES');

        foreach ((array)$cqs->getPoints() as $existing_cq) {
            $influxdb->query(sprintf('DROP CONTINUOUS QUERY %s ON %s', $existing_cq['name'], $db_name));
        }

        // Create continuous queries
        $downsample_retentions = ['1h', '1d'];

        foreach ($downsample_retentions as $dr) {
            $cq_name = 'cq_' . $dr;
            $cq_fields = 'min(value) AS min, mean(value) AS value, max(value) AS max';

            $influxdb->query(sprintf('CREATE CONTINUOUS QUERY %s ON %s BEGIN SELECT %s INTO "%s".:MEASUREMENT FROM /.*/ GROUP BY time(%s) END',
                $cq_name, $db_name, $cq_fields, $dr, $dr));
        }

        $io->writeln(__('Continuous queries created.'));

        // Print debug information
        if (!$settings->isProduction()) {
            $rps_raw = $influxdb->query('SHOW RETENTION POLICIES');
            $rps = (array)$rps_raw->getPoints();

            $io->writeln(print_r($rps, true));

            $cqs_raw = $influxdb->query('SHOW CONTINUOUS QUERIES');
            $cqs = [];

            foreach ((array)$cqs_raw->getPoints() as $cq) {
                $cqs[$cq['name']] = $cq['query'];
            }

            $io->writeln(print_r($cqs, true));
        }

        $io->writeln(__('InfluxDB databases created.'));
        return 0;
    }
}
