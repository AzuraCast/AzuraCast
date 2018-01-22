<?php
namespace AzuraCast\Console\Command;

use Doctrine\ORM\EntityManager;
use Entity\Station;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartRadio extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:radio:restart')
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
        $supervisor = $this->di[\Supervisor\Supervisor::class];

        /** @var EntityManager $em */
        $em = $this->di[EntityManager::class];
        $stations = $em->getRepository(Station::class)->findAll();

        $supervisor->stopAllProcesses();

        foreach ($stations as $station) {
            /** @var Station $station */

            \App\Debug::log('Restarting station #' . $station->getId() . ': ' . $station->getName());

            $station->writeConfiguration($this->di);

            \App\Debug::divider();
        }

        $supervisor->startAllProcesses();
    }
}