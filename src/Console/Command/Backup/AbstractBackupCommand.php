<?php

declare(strict_types=1);

namespace App\Console\Command\Backup;

use App\Console\Command\CommandAbstract;
use App\Console\Command\Traits\PassThruProcess;
use App\Environment;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractBackupCommand extends CommandAbstract
{
    use PassThruProcess;

    public function __construct(
        protected Environment $environment,
        protected EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function getDatabaseSettingsAsCliFlags(): array
    {
        $connSettings = $this->environment->getDatabaseSettings();

        $commandEnvVars = [
            'DB_DATABASE' => $connSettings['dbname'],
            'DB_USERNAME' => $connSettings['user'],
            'DB_PASSWORD' => $connSettings['password'],
        ];

        $commandFlags = [
            '--user=$DB_USERNAME',
            '--password=$DB_PASSWORD',
        ];

        if (isset($connSettings['unix_socket'])) {
            $commandFlags[] = '--socket=$DB_SOCKET';
            $commandEnvVars['DB_SOCKET'] = $connSettings['unix_socket'];
        } else {
            $commandFlags[] = '--host=$DB_HOST';
            $commandFlags[] = '--port=$DB_PORT';
            $commandEnvVars['DB_HOST'] = $connSettings['host'];
            $commandEnvVars['DB_PORT'] = $connSettings['port'];
        }

        return [$commandFlags, $commandEnvVars];
    }
}
