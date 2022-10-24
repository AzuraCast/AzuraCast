<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity;
use App\Environment;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:setup:initialize',
    description: 'Ensure key settings are initialized within AzuraCast.',
)]
final class InitializeCommand extends CommandAbstract
{
    public function __construct(
        private readonly Environment $environment,
        private readonly Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(__('Initialize AzuraCast'));
        $io->writeln(__('Initializing essential settings...'));

        $io->listing(
            [
                sprintf(
                    __('Environment: %s'),
                    $this->environment->getAppEnvironmentEnum()->getName()
                ),
                sprintf(
                    __('Installation Method: %s'),
                    $this->environment->isDocker() ? 'Docker' : 'Ansible'
                ),
            ]
        );

        $io->newLine();
        $io->section(__('Running Database Migrations'));

        $this->runCommand(
            $output,
            'azuracast:setup:migrate'
        );

        $io->newLine();
        $io->section(__('Generating Database Proxy Classes'));

        $this->runCommand($output, 'orm:generate-proxies');

        $io->newLine();
        $io->section(__('Reload System Data'));

        $this->runCommand($output, 'cache:clear');

        // Ensure default storage locations exist.
        $this->storageLocationRepo->createDefaultStorageLocations();

        $io->newLine();
        $io->success(
            [
                __('AzuraCast is now initialized.'),
            ]
        );

        return 0;
    }
}
