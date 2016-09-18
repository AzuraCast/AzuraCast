<?php
namespace App\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartRadio extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('radio:restart')
            ->setDescription('Restart all radio stations.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \App\Debug::setEchoMode(true);

        \App\Debug::log('Restarting all radio stations...');
        \App\Debug::divider();

        $stations = \Entity\Station::fetchAll();

        foreach($stations as $station)
        {
            \App\Debug::log('Restarting station #'.$station->id.': '.$station->name);

            $backend = $station->getBackendAdapter();
            $frontend = $station->getFrontendAdapter();

            $backend->stop();
            $frontend->stop();

            $frontend->write();
            $backend->write();

            $frontend->start();
            $backend->start();

            \App\Debug::divider();
        }
    }
}