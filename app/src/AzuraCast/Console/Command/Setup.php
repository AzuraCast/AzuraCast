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

class Setup extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:setup')
            ->setDescription('Run all general AzuraCast setup steps.')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Only update the existing installation.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $update_only = (bool)$input->getOption('update');

        $this->runCommand($output, 'azuracast:setup:influx');
        $this->runCommand($output, 'cache:clear');
        $this->runCommand($output, 'migrations:migrate', [
            '--allow-no-migration' => true,
        ]);

        $this->runCommand($output, 'orm:generate-proxies');

        if (!APP_IN_PRODUCTION && !$update_only) {
            $this->runCommand($output, 'azuracast:setup:fixtures');
        }

        $this->runCommand($output, 'cache:clear');
        $this->runCommand($output, 'azuracast:radio:restart');

        $output->writeln('AzuraCast is updated and ready to run.');
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