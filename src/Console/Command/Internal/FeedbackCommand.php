<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Radio\Backend\Liquidsoap\Feedback;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:internal:feedback',
    description: 'Send upcoming song feedback from the AutoDJ back to AzuraCast.',
)]
class FeedbackCommand extends CommandAbstract
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Feedback $feedback,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-id', InputArgument::REQUIRED)
            ->addOption('song', 's', InputOption::VALUE_OPTIONAL, '', '')
            ->addOption('media', 'm', InputOption::VALUE_OPTIONAL, '', '')
            ->addOption('playlist', 'p', InputOption::VALUE_OPTIONAL, '', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationId = (int)$input->getArgument('station-id');
        $song = $input->getOption('song');
        $media = $input->getOption('media');
        $playlist = $input->getOption('playlist');

        $station = $this->em->find(Entity\Station::class, $stationId);

        if (!($station instanceof Entity\Station)) {
            $io->write('false');
            return 0;
        }

        try {
            ($this->feedback)($station, [
                'song_id'     => $song,
                'media_id'    => $media,
                'playlist_id' => $playlist,
            ]);

            $io->write('OK');
            return 0;
        } catch (Exception $e) {
            $io->write('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
