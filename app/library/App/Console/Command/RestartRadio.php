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

        /** @var \Supervisor\Supervisor */
        $supervisor = $this->di['supervisor'];

        /** @var EntityManager $em */
        $em = $this->di['em'];
        $stations = $em->getRepository(Station::class)->findAll();

        $supervisor->stopAllProcesses();

        // Get rid of any processes running from legacy tooling.
        exec('sudo killall -9 liquidsoap icecast2 sc_serv');

        foreach($stations as $station)
        {
            /** @var Station $station */

            \App\Debug::log('Restarting station #'.$station->id.': '.$station->name);

            $station->writeConfiguration($this->di);

            \App\Debug::divider();
        }

        $supervisor->startAllProcesses();
    }
}