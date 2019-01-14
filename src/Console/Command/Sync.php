<?php
namespace App\Console\Command;

use App;
use App\Sync\Runner;
use Azura\Console\Command\CommandAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends CommandAbstract
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
                InputArgument::OPTIONAL,
                'The task to run (nowplaying,short,medium,long).',
                'nowplaying'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Runner $sync */
        $sync = $this->get(Runner::class);

        switch ($input->getArgument('task')) {
            case 'long':
                $sync->syncLong();
                break;

            case 'medium':
                $sync->syncMedium();
                break;

            case 'short':
                $sync->syncShort();
                break;

            case 'nowplaying':
            default:
                define('NOWPLAYING_SEGMENT', 1);
                $sync->syncNowplaying();
                break;
        }

        return 0;
    }
}
