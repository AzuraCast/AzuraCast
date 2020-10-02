<?php
namespace App\Console\Command\Influx;

use App\Console\Command\CommandAbstract;
use InfluxDB\Database;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueryCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Database $influxdb,
        $query
    ) {
        $output = $influxdb->query($query);
        $parsed = json_decode($output->getRaw(), true, 512, JSON_THROW_ON_ERROR);

        $io->writeln(json_encode($parsed, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        return 0;
    }
}
