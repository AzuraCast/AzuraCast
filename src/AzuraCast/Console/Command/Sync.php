<?php
namespace AzuraCast\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use App\Sync\Manager;

class Sync extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sync:run')
            ->setDescription('Run one or more scheduled synchronization tasks.')
            ->addArgument(
                'task',
                null,
                'The task to run (nowplaying,short,medium,long).',
                'nowplaying'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \App\Sync $sync */
        $sync = $this->di['sync'];

        switch($input->getArgument('task'))
        {
            case 'long':
                $output->writeln('Running Long (1-hour) Sync...');

                $sync->syncLong();
            break;

            case 'medium':
                $output->writeln('Running Medium (5-minutes) Sync...');

                $sync->syncMedium();
            break;

            case 'short':
                $output->writeln('Running Short (1-minute) Sync...');

                $sync->syncShort();
            break;

            case 'nowplaying':
            default:
                $output->writeln('Running Now-Playing (15-second) Sync...');

                define('NOWPLAYING_SEGMENT', 1);
                $sync->syncNowplaying();
            break;
        }
    }
}