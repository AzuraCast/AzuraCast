<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Radio\AutoDJ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:internal:nextsong',
    description: 'Return the next song to the AutoDJ.',
)]
class NextSongCommand extends CommandAbstract
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected AutoDJ\Annotations $annotations,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-id', InputArgument::REQUIRED)
            ->addOption('as-autodj', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationId = (int)$input->getArgument('station-id');
        $asAutodj = (bool)$input->getOption('as-autodj');

        $station = $this->em->find(Entity\Station::class, $stationId);

        if (!($station instanceof Entity\Station)) {
            $io->write('false');
            return 0;
        }

        $io->write($this->annotations->annotateNextSong($station, $asAutodj));
        return 0;
    }
}
