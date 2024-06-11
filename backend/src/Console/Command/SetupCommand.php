<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Repository\StorageLocationRepository;
use App\Service\AzuraCastCentral;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:setup',
    description: 'Run all general AzuraCast setup steps.',
)]
final class SetupCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly AzuraCastCentral $acCentral,
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('update', null, InputOption::VALUE_NONE)
            ->addOption('load-fixtures', null, InputOption::VALUE_NONE)
            ->addOption('release', null, InputOption::VALUE_NONE)
            ->addOption('init', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $update = (bool)$input->getOption('update');
        $loadFixtures = (bool)$input->getOption('load-fixtures');
        $isInit = (bool)$input->getOption('init');

        if ($isInit) {
            $update = true;
            $loadFixtures = false;
        }

        if (!$update && !$this->environment->isProduction()) {
            $loadFixtures = true;
        }

        // Header display
        if ($isInit) {
            $io->title(__('AzuraCast Initializing...'));
        } else {
            $io->title(__('AzuraCast Setup'));
            $io->writeln(
                __('Welcome to AzuraCast. Please wait while some key dependencies of AzuraCast are set up...')
            );
            $io->newLine();
        }

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

        if ($loadFixtures) {
            $io->section(__('Installing Data Fixtures'));

            $this->runCommand($output, 'azuracast:setup:fixtures');
            $io->newLine();
        }

        $io->section(__('Refreshing All Stations'));

        $this->runCommand($output, 'azuracast:station-queues:clear');

        $restartArgs = [];
        if ($this->environment->isDocker()) {
            $restartArgs['--no-supervisor-restart'] = true;
        }

        $this->runCommand(
            $output,
            'azuracast:radio:restart',
            $restartArgs
        );

        if ($isInit) {
            return 0;
        }

        // Update system setting logging when updates were last run.
        $settings = $this->readSettings();
        $settings->updateUpdateLastRun();
        $this->writeSettings($settings);

        if ($update) {
            $io->success(
                [
                    __('AzuraCast is now updated to the latest version!'),
                ]
            );
        } else {
            $publicIp = $this->acCentral->getIp(false);

            /** @noinspection HttpUrlsUsage */
            $io->success(
                [
                    __('AzuraCast installation complete!'),
                    sprintf(
                        __('Visit %s to complete setup.'),
                        'http://' . $publicIp
                    ),
                ]
            );
        }

        return 0;
    }
}
