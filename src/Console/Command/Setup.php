<?php
namespace App\Console\Command;

use App\Entity;
use App\Service\AzuraCastCentral;
use App\Utilities;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Setup extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:setup')
            ->setDescription(__('Run all general AzuraCast setup steps.'))
            ->addOption('update', null, InputOption::VALUE_NONE, __('Only update the existing installation.'))
            ->addOption('load-fixtures', null, InputOption::VALUE_NONE, __('Load predefined fixtures (for development purposes).'))
            ->addOption('release', null, InputOption::VALUE_NONE, __('Used for updating only to a tagged release.'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $update_only = (bool)$input->getOption('update');
        $load_fixtures = (bool)$input->getOption('load-fixtures');

        $io = new SymfonyStyle($input, $output);
        $io->title(__('AzuraCast Setup'));
        $io->writeln(__('Welcome to AzuraCast. Please wait while some key dependencies of AzuraCast are set up...'));

        $io->listing([
            __('Environment: %s', ucfirst(APP_APPLICATION_ENV)),
            __('Installation Method: %s', APP_INSIDE_DOCKER ? 'Docker' : 'Ansible'),
        ]);

        if ($update_only) {
            $io->note(__('Running in update mode.'));

            if (!APP_INSIDE_DOCKER) {
                $io->section(__('Migrating Legacy Configuration'));
                $this->runCommand($output, 'azuracast:config:migrate');
                $io->newLine();
            }
        }

        $io->section(__('Setting Up InfluxDB'));

        $this->runCommand($output, 'azuracast:setup:influx');
        $this->runCommand($output, 'cache:clear');

        $io->newLine();
        $io->section(__('Running Database Migrations'));

        $this->runCommand($output, 'migrations:migrate', [
            '--allow-no-migration' => true,
        ]);

        $io->newLine();
        $io->section(__('Generating Database Proxy Classes'));

        $this->runCommand($output, 'orm:generate-proxies');

        if ($load_fixtures || (!APP_IN_PRODUCTION && !$update_only)) {
            $io->newLine();
            $io->section(__('Installing Data Fixtures'));

            $this->runCommand($output, 'azuracast:setup:fixtures');
        }

        $io->newLine();
        $io->section(__('Refreshing All Stations'));

        $this->runCommand($output, 'cache:clear');
        $this->runCommand($output, 'azuracast:radio:restart');

        // Clear update information.

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        $settings_repo->setSetting(Entity\Settings::UPDATE_RESULTS, null);
        $settings_repo->setSetting(Entity\Settings::UPDATE_LAST_RUN, time());

        $io->newLine();

        if ($update_only) {
            $io->success([
                __('AzuraCast is now updated to the latest version!'),
            ]);
        } else {
            /** @var AzuraCastCentral $ac_central */
            $ac_central = $this->get(AzuraCastCentral::class);
            $public_ip = $ac_central->getIp(false);

            $io->success([
                __('AzuraCast installation complete!'),
                __('Visit %s to complete setup.', 'http://'.$public_ip),
            ]);
        }

        $settings_repo->deleteSetting(Entity\Settings::UNIQUE_IDENTIFIER);
        $settings_repo->deleteSetting(Entity\Settings::EXTERNAL_IP);

        return 0;
    }
}
