<?php
namespace App\Console\Command;

use Doctrine\ORM\EntityManager;
use Entity\Station;
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

        /** @var EntityManager $em */
        $em = $this->di['em'];
        $stations = $em->getRepository(Station::class)->findAll();

        foreach($stations as $station)
        {
            /** @var Station $station */

            \App\Debug::log('Restarting station #'.$station->id.': '.$station->name);

            $backend = $station->getBackendAdapter($this->di);
            $frontend = $station->getFrontendAdapter($this->di);

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