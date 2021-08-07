<?php

declare(strict_types=1);

namespace App\Console\Command\Debug;

use App\Console\Command\CommandAbstract;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Style\SymfonyStyle;

class OptimizeTablesCommand extends CommandAbstract
{
    public function __invoke(SymfonyStyle $io, Connection $db): int
    {
        $io->title('Optimizing Database Tables...');

        foreach ($db->fetchAllAssociative('SHOW TABLES') as $tableRow) {
            $table = reset($tableRow);

            $io->listing([$table]);
            $db->executeQuery('OPTIMIZE TABLE ' . $db->quoteIdentifier($table));
        }

        $io->success('All tables optimized.');
        return 0;
    }
}
