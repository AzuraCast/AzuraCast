<?php

declare(strict_types=1);

namespace App\Console\Command\Debug;

use App\Console\Command\CommandAbstract;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:debug:optimize-tables',
    description: 'Optimize all tables in the database.',
)]
final class OptimizeTablesCommand extends CommandAbstract
{
    public function __construct(
        private readonly Connection $db
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Optimizing Database Tables...');

        foreach ($this->db->fetchAllAssociative('SHOW TABLES') as $tableRow) {
            $table = reset($tableRow);

            $io->listing([$table]);
            $this->db->executeQuery('OPTIMIZE TABLE ' . $this->db->quoteIdentifier($table));
        }

        $io->success('All tables optimized.');
        return 0;
    }
}
