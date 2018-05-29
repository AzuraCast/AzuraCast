<?php
namespace AzuraCast\Console\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Setup extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:setup')
            ->setDescription('Run all general AzuraCast setup steps.')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Only update the existing installation.')
            ->addOption('load-fixtures', null, InputOption::VALUE_NONE, 'Load predefined fixtures (for development purposes).');
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
            'Installation Method: '.((APP_INSIDE_DOCKER) ? 'Docker' : 'Traditional'),
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

        $io->newLine();
        $io->success('AzuraCast setup complete!');
        return 0;
    }

    protected function runCommand(OutputInterface $output, $command_name, $command_args = [])
    {
        $command = $this->getApplication()->find($command_name);

        $input = new ArrayInput(['command' => $command_name] + $command_args);
        $input->setInteractive(false);

        $command->run($input, $output);
    }
}