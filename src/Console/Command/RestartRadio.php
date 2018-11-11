<?php
namespace App\Console\Command;

use App\Radio\Configuration;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use App\Entity\Station;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartRadio extends CommandAbstract
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
        $output->writeLn('Restarting all radio stations...');

        /** @var \Supervisor\Supervisor $supervisor */
        $supervisor = $this->get(\Supervisor\Supervisor::class);

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        /** @var Configuration $configuration */
        $configuration = $this->get(Configuration::class);

        /** @var Station[] $stations */
        $stations = $em->getRepository(Station::class)->findAll();

        $supervisor->stopAllProcesses();

        foreach ($stations as $station) {
            $output->writeLn('Restarting station #' . $station->getId() . ': ' . $station->getName());
            $configuration->writeConfiguration($station);

            $station->setHasStarted(true);
            $station->setNeedsRestart(false);

            $em->persist($station);
        }

        $supervisor->startAllProcesses();
        $em->flush();

        return 0;
    }
}
