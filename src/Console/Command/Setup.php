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
            ->setDescription('Run all general AzuraCast setup steps.')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Only update the existing installation.')
            ->addOption('load-fixtures', null, InputOption::VALUE_NONE, 'Load predefined fixtures (for development purposes).')
            ->addOption('release', null, InputOption::VALUE_NONE, 'Used for updating only to a tagged release.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $update_only = (bool)$input->getOption('update');
        $load_fixtures = (bool)$input->getOption('load-fixtures');

        $io = new SymfonyStyle($input, $output);
        $io->title('AzuraCast Setup');
        $io->writeln('Welcome to AzuraCast. Please wait while some key dependencies of AzuraCast are set up...');

        $io->listing([
            'Environment: '.ucfirst(APP_APPLICATION_ENV),
            'Installation Method: '.((APP_INSIDE_DOCKER) ? 'Docker' : 'Ansible'),
        ]);

        if ($update_only) {
            $io->note('Running in update mode.');

            if (!APP_INSIDE_DOCKER) {
                $io->section('Migrating Legacy Configuration');
                $this->runCommand($output, 'azuracast:config:migrate');
                $io->newLine();
            }
        }

        $io->section('Setting Up InfluxDB');

        $this->runCommand($output, 'azuracast:setup:influx');
        $this->runCommand($output, 'cache:clear');

        $io->newLine();
        $io->section('Running Database Migrations');

        $this->runCommand($output, 'migrations:migrate', [
            '--allow-no-migration' => true,
        ]);

        $io->newLine();
        $io->section('Generating Database Proxy Classes');

        $this->runCommand($output, 'orm:generate-proxies');

        if ($load_fixtures || (!APP_IN_PRODUCTION && !$update_only)) {
            $io->newLine();
            $io->section('Installing Data Fixtures');

            $this->runCommand($output, 'azuracast:setup:fixtures');
        }

        $io->newLine();
        $io->section('Refreshing All Stations');

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
                'AzuraCast is now updated to the latest version!',
            ]);
        } else {
            /** @var AzuraCastCentral $ac_central */
            $ac_central = $this->get(AzuraCastCentral::class);
            $public_ip = $ac_central->getIp();

            $io->success([
                'AzuraCast installation complete!',
                'Visit http://'.$public_ip.' to complete setup.',
            ]);
        }

        return 0;
    }
}
