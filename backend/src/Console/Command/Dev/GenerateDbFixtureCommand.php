<?php

declare(strict_types=1);

namespace App\Console\Command\Dev;

use App\Console\Command\AbstractDatabaseCommand;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:dev:generate-db-fixture',
    description: 'Generate the Database Fixture that MariaDB loads on new installs.',
)]
final class GenerateDbFixtureCommand extends AbstractDatabaseCommand
{
    public function __construct(
        private readonly ConfigurationLoader $migrationConfig,
        private readonly Connection $db,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sqlDumpPath = $this->environment->getBaseDirectory() . '/util/docker/mariadb/mariadb/db.sql';

        $this->dumpTableStructure($io, $sqlDumpPath);
        $this->dumpMigrationTableContents($io, $sqlDumpPath);

        $io->success(
            [
                'New DB fixture generated.',
            ]
        );
        return 0;
    }

    private function dumpTableStructure(
        OutputInterface $output,
        string $sqlDumpPath
    ): void {
        [$commandFlags, $commandEnvVars] = $this->getDatabaseSettingsAsCliFlags();

        $commandFlags[] = '--skip-opt';
        $commandFlags[] = '--create-options';
        $commandFlags[] = '--add-drop-database';
        $commandFlags[] = '--no-data';
        $commandFlags[] = '--skip-add-drop-table';
        $commandFlags[] = '--default-character-set=UTF8MB4';

        $commandEnvVars['DB_DEST'] = $sqlDumpPath;

        $this->passThruProcess(
            $output,
            'mariadb-dump ' . implode(' ', $commandFlags) . ' --databases $DB_DATABASE > $DB_DEST',
            dirname($sqlDumpPath),
            $commandEnvVars
        );
    }

    private function dumpMigrationTableContents(
        OutputInterface $output,
        string $sqlDumpPath
    ): void {
        $migrationMetaStorage = $this->migrationConfig->getConfiguration()
            ->getMetadataStorageConfiguration();

        assert($migrationMetaStorage instanceof TableMetadataStorageConfiguration);

        $tableName = $migrationMetaStorage->getTableName();

        $this->db->update(
            $tableName,
            [
                'executed_at' => '2016-01-01 00:00:00',
                'execution_time' => null,
            ]
        );

        [$commandFlags, $commandEnvVars] = $this->getDatabaseSettingsAsCliFlags();

        $commandFlags[] = '--skip-opt';
        $commandFlags[] = '--extended-insert';
        $commandFlags[] = '--no-create-db';
        $commandFlags[] = '--no-create-info';
        $commandFlags[] = '--default-character-set=UTF8MB4';

        $commandEnvVars['DB_DEST'] = $sqlDumpPath;
        $commandEnvVars['DB_TABLE'] = $tableName;

        $this->passThruProcess(
            $output,
            'mariadb-dump ' . implode(' ', $commandFlags) . ' $DB_DATABASE $DB_TABLE >> $DB_DEST',
            dirname($sqlDumpPath),
            $commandEnvVars
        );
    }
}
