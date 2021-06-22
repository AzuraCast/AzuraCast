<?php

namespace App\Console\Command;

use App\Entity;
use App\Environment;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitializeCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        Environment $environment,
        ContainerInterface $di,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationRepository $stationRepo,
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
        $this->runCommand($output, 'queue:clear');

        $stationRepo->clearNowPlaying();

        // Clear settings that should be reset upon update.
        $settings = $settingsRepo->readSettings();
        $settings->setNowplaying(null);
        $settings->updateUpdateLastRun();
        $settings->setUpdateResults(null);

        if ('127.0.0.1' !== $settings->getExternalIp()) {
            $settings->setExternalIp(null);
        }

        $settingsRepo->writeSettings($settings);

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
