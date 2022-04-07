<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Radio\Backend\Liquidsoap\Command\AbstractCommand;
use App\Radio\Enums\LiquidsoapCommands;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:internal:liquidsoap',
    description: 'Handle Liquidsoap API calls.',
)]
class LiquidsoapCommand extends CommandAbstract
{
    public function __construct(
        protected StationRepository $stationRepo,
        protected ContainerInterface $di,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED)
            ->addArgument('station-id', InputArgument::REQUIRED)
            ->addOption(
                'as-autodj',
                null,
                InputOption::VALUE_NONE,
                'Whether the task is executing as the actual AutoDJ or as a test.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationId = $input->getArgument('station-id');
        $action = $input->getArgument('action');
        $asAutoDj = (bool)$input->getOption('as-autodj');

        $payload = trim(getenv('PAYLOAD') ?: '');
        if (!empty($payload)) {
            $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $payload = [];
        }

        try {
            $station = $this->stationRepo->findByIdentifier($stationId);
            if (!($station instanceof Entity\Station)) {
                throw new \LogicException('Station not found.');
            }

            $command = LiquidsoapCommands::tryFrom($action);
            if (null === $command || !$this->di->has($command->getClass())) {
                throw new \LogicException('Command not found.');
            }

            /** @var AbstractCommand $commandObj */
            $commandObj = $this->di->get($command->getClass());

            $result = $commandObj->run($station, $asAutoDj, $payload);
            $io->writeln($result);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Liquidsoap command "%s" error: %s',
                    $action,
                    $e->getMessage()
                ),
                [
                    'station' => $stationId,
                    'payload' => $payload,
                    'as-autodj' => $asAutoDj,
                ]
            );

            $io->writeln('false');
            return 1;
        }

        return 0;
    }
}
