<?php

namespace App\Console\Command;

use App\Entity;
use App\Service\AzuraCastCentral;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        Settings $settings,
        ContainerInterface $di,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationRepository $stationRepo,
        AzuraCastCentral $acCentral,
        bool $update = false,
        bool $loadFixtures = false
    ): int {
        $io->title(__('AzuraCast Setup'));
        $io->writeln(__('Welcome to AzuraCast. Please wait while some key dependencies of AzuraCast are set up...'));

        $io->listing([
            __('Environment: %s', ucfirst($settings[Settings::APP_ENV])),
            __('Installation Method: %s', $settings->isDocker() ? 'Docker' : 'Ansible'),
        ]);

        if ($update) {
            $io->note(__('Running in update mode.'));

            if (!$settings->isDocker()) {
                $io->section(__('Migrating Legacy Configuration'));
                $this->runCommand($output, 'azuracast:config:migrate');
                $io->newLine();
            }
        }

        /** @var EntityManagerInterface $em */
        $em = $di->get(EntityManagerInterface::class);
        $conn = $em->getConnection();

        $io->newLine();
        $io->section(__('Running Database Migrations'));

        $conn->ping();
        $this->runCommand($output, 'migrations:migrate', [
            '--allow-no-migration' => true,
        ]);

        $io->newLine();
        $io->section(__('Generating Database Proxy Classes'));

        $conn->ping();
        $this->runCommand($output, 'orm:generate-proxies');

        if ($loadFixtures || (!$settings->isProduction() && !$update)) {
            $io->newLine();
            $io->section(__('Installing Data Fixtures'));

            $this->runCommand($output, 'azuracast:setup:fixtures');
        }

        $io->newLine();
        $io->section(__('Reload System Data'));

        $this->runCommand($output, 'cache:clear');

        $this->runCommand($output, 'queue:clear');

        $settingsRepo->deleteSetting(Entity\Settings::NOWPLAYING);
        $stationRepo->clearNowPlaying();

        $io->newLine();
        $io->section(__('Refreshing All Stations'));

        $conn->ping();
        $this->runCommand($output, 'azuracast:radio:restart');

        // Clear settings that should be reset upon update.
        $settingsRepo->setSetting(Entity\Settings::UPDATE_LAST_RUN, time());
        $settingsRepo->deleteSetting(Entity\Settings::UPDATE_RESULTS);
        $settingsRepo->deleteSetting(Entity\Settings::UNIQUE_IDENTIFIER);
        $settingsRepo->deleteSetting(Entity\Settings::EXTERNAL_IP);

        $io->newLine();

        if ($update) {
            $io->success([
                __('AzuraCast is now updated to the latest version!'),
            ]);
        } else {
            $public_ip = $acCentral->getIp(false);

            $io->success([
                __('AzuraCast installation complete!'),
                __('Visit %s to complete setup.', 'http://' . $public_ip),
            ]);
        }

        return 0;
    }
}
