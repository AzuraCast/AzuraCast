<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Entity\Repository\SettingsRepository;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:internal:auth',
    description: 'Authorize a streamer to connect as a source for the radio service.',
)]
class DjAuthCommand extends CommandAbstract
{
    public function __construct(
        protected Adapters $adapters,
        protected EntityManagerInterface $em,
        protected SettingsRepository $settingsRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-id', InputArgument::REQUIRED)
            ->addOption('dj-user', null, InputOption::VALUE_REQUIRED, '', '')
            ->addOption('dj-password', null, InputOption::VALUE_REQUIRED, '', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationId = (int)$input->getArgument('station-id');
        $djUser = $input->getOption('dj-user');
        $djPassword = $input->getOption('dj-password');

        $station = $this->em->getRepository(Entity\Station::class)->find($stationId);

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            $io->write('false');
            return 0;
        }

        $adapter = $this->adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $response = $adapter->authenticateStreamer($station, $djUser, $djPassword);
            $io->write($response);
            return 0;
        }

        $io->write('false');
        return 0;
    }
}
