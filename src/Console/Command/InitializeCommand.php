<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity;
use App\Environment;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitializeCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        Environment $environment,
        Entity\Repository\StorageLocationRepository $storageLocationRepo
    ): int {
        $io->title(__('Initialize AzuraCast'));
        $io->writeln(__('Initializing essential settings...'));

        $io->listing(
            [
                __('Environment: %s', ucfirst($environment->getAppEnvironment())),
                __('Installation Method: %s', $environment->isDocker() ? 'Docker' : 'Ansible'),
            ]
        );

        $io->newLine();
        $io->section(__('Running Database Migrations'));

        $this->runCommand(
            $output,
            'migrations:migrate',
            [
                '--allow-no-migration' => true,
            ]
        );

        $io->newLine();
        $io->section(__('Generating Database Proxy Classes'));

        $this->runCommand($output, 'orm:generate-proxies');

        $io->newLine();
        $io->section(__('Reload System Data'));

        $this->runCommand($output, 'cache:clear');

        // Ensure default storage locations exist.
        $storageLocationRepo->createDefaultStorageLocations();

        $io->newLine();
        $io->success(
            [
                __('AzuraCast is now initialized.'),
            ]
        );

        return 0;
    }
}
