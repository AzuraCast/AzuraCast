<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity;
use App\Environment;
use App\Service\AzuraCastCentral;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        Environment $environment,
        ContainerInterface $di,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationRepository $stationRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        AzuraCastCentral $acCentral,
        bool $update = false,
        bool $loadFixtures = false
    ): int {
        $io->title(__('AzuraCast Setup'));
        $io->writeln(__('Welcome to AzuraCast. Please wait while some key dependencies of AzuraCast are set up...'));

        $this->runCommand($output, 'azuracast:setup:initialize');

        if ($loadFixtures || (!$environment->isProduction() && !$update)) {
            $io->newLine();
            $io->section(__('Installing Data Fixtures'));

            $this->runCommand($output, 'azuracast:setup:fixtures');
        }

        $io->newLine();
        $io->section(__('Refreshing All Stations'));

        $this->runCommand($output, 'azuracast:radio:restart');

        // Update system setting logging when updates were last run.
        $settings = $settingsRepo->readSettings();
        $settings->updateUpdateLastRun();
        $settingsRepo->writeSettings($settings);

        if ($update) {
            $io->success(
                [
                    __('AzuraCast is now updated to the latest version!'),
                ]
            );
        } else {
            $public_ip = $acCentral->getIp(false);

            /** @noinspection HttpUrlsUsage */
            $io->success(
                [
                    __('AzuraCast installation complete!'),
                    __('Visit %s to complete setup.', 'http://' . $public_ip),
                ]
            );
        }

        return 0;
    }
}
