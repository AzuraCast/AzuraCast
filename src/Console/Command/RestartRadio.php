<?php
namespace App\Console\Command;

use App\Entity\Station;
use App\Radio\Configuration;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $io = new SymfonyStyle($input, $output);

        $io->section('Restarting all radio stations...');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        /** @var Configuration $configuration */
        $configuration = $this->get(Configuration::class);

        /** @var Station[] $stations */
        $stations = $em->getRepository(Station::class)->findAll();

        $io->progressStart(count($stations));

        foreach ($stations as $station) {
            $configuration->writeConfiguration($station, false, true);

            $station->setHasStarted(true);
            $station->setNeedsRestart(false);

            $em->persist($station);
            $em->flush($station);

            $io->progressAdvance();
        }

        $io->progressFinish();

        return 0;
    }
}
