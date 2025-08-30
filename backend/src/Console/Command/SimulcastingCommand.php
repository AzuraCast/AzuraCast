<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Enums\SimulcastingStatus;
use App\Entity\Repository\StationRepository;
use App\Entity\Repository\SimulcastingRepository;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Simulcasting\SimulcastingManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:simulcasting',
    description: 'Manage simulcasting streams',
)]
final class SimulcastingCommand extends Command
{
    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly SimulcastingRepository $simulcastingRepo,
        private readonly SimulcastingManager $simulcastingManager,
        private readonly Liquidsoap $liquidsoap
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'station',
            's',
            InputOption::VALUE_REQUIRED,
            'Station ID or short name'
        );

        $this->addOption(
            'action',
            'a',
            InputOption::VALUE_REQUIRED,
            'Action to perform: list, start, stop, status',
            'list'
        );

        $this->addOption(
            'stream-id',
            'i',
            InputOption::VALUE_REQUIRED,
            'Simulcasting stream ID (required for start/stop actions)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationId = $input->getOption('station');
        $action = $input->getOption('action');
        $streamId = $input->getOption('stream-id');

        if (!$stationId) {
            $io->error('Station ID or short name is required');
            return Command::FAILURE;
        }

        $station = $this->stationRepo->findByIdentifier($stationId);
        if (!$station) {
            $io->error('Station not found');
            return Command::FAILURE;
        }

        switch ($action) {
            case 'list':
                return $this->listStreams($io, $station);

            case 'start':
                if (!$streamId) {
                    $io->error('Stream ID is required for start action');
                    return Command::FAILURE;
                }
                return $this->startStream($io, $station, (int)$streamId);

            case 'stop':
                if (!$streamId) {
                    $io->error('Stream ID is required for stop action');
                    return Command::FAILURE;
                }
                return $this->stopStream($io, $station, (int)$streamId);

            case 'status':
                if (!$streamId) {
                    $io->error('Stream ID is required for status action');
                    return Command::FAILURE;
                }
                return $this->getStreamStatus($io, $station, (int)$streamId);

            default:
                $io->error('Invalid action. Use: list, start, stop, or status');
                return Command::FAILURE;
        }
    }

    private function listStreams(SymfonyStyle $io, \App\Entity\Station $station): int
    {
        $streams = $this->simulcastingRepo->findByStation($station);

        if (empty($streams)) {
            $io->info("No simulcasting streams found for station '{$station->getName()}'");
            return Command::SUCCESS;
        }

        $io->title("Simulcasting Streams for '{$station->getName()}'");

        $tableData = [];
        foreach ($streams as $stream) {
            $tableData[] = [
                $stream->getId(),
                $stream->getName(),
                $stream->getAdapter(),
                $stream->getStatus()->value,
                $stream->getCreatedAt()->format('Y-m-d H:i:s'),
                $stream->getErrorMessage() ?: '-',
            ];
        }

        $io->table(
            ['ID', 'Name', 'Adapter', 'Status', 'Created', 'Error'],
            $tableData
        );

        return Command::SUCCESS;
    }

    private function startStream(SymfonyStyle $io, \App\Entity\Station $station, int $streamId): int
    {
        $stream = $this->simulcastingRepo->find($streamId);
        if (!$stream || $stream->getStation()->getId() !== $station->getId()) {
            $io->error('Stream not found');
            return Command::FAILURE;
        }

        if ($stream->getStatus() === SimulcastingStatus::Running) {
            $io->info("Stream '{$stream->getName()}' is already running");
            return Command::SUCCESS;
        }

        $backend = $station->getBackend();
        if (!$backend instanceof Liquidsoap) {
            $io->error('Station does not use LiquidSoap backend');
            return Command::FAILURE;
        }

        $success = $this->simulcastingManager->startSimulcasting($stream, $backend);
        if ($success) {
            $io->success("Started simulcasting stream '{$stream->getName()}'");
            $io->info('LiquidSoap configuration will be updated automatically');
            return Command::SUCCESS;
        } else {
            $io->error("Failed to start simulcasting stream '{$stream->getName()}'");
            return Command::FAILURE;
        }
    }

    private function stopStream(SymfonyStyle $io, \App\Entity\Station $station, int $streamId): int
    {
        $stream = $this->simulcastingRepo->find($streamId);
        if (!$stream || $stream->getStation()->getId() !== $station->getId()) {
            $io->error('Stream not found');
            return Command::FAILURE;
        }

        if ($stream->getStatus() === SimulcastingStatus::Stopped) {
            $io->info("Stream '{$stream->getName()}' is already stopped");
            return Command::SUCCESS;
        }

        $backend = $station->getBackend();
        if (!$backend instanceof Liquidsoap) {
            $io->error('Station does not use LiquidSoap backend');
            return Command::FAILURE;
        }

        $success = $this->simulcastingManager->stopSimulcasting($stream, $backend);
        if ($success) {
            $io->success("Stopped simulcasting stream '{$stream->getName()}'");
            $io->info('LiquidSoap configuration will be updated automatically');
            return Command::SUCCESS;
        } else {
            $io->error("Failed to stop simulcasting stream '{$stream->getName()}'");
            return Command::FAILURE;
        }
    }

    private function getStreamStatus(SymfonyStyle $io, \App\Entity\Station $station, int $streamId): int
    {
        $stream = $this->simulcastingRepo->find($streamId);
        if (!$stream || $stream->getStation()->getId() !== $station->getId()) {
            $io->error('Stream not found');
            return Command::FAILURE;
        }

        $io->title("Status for Stream '{$stream->getName()}'");

        $statusData = [
            ['ID', $stream->getId()],
            ['Name', $stream->getName()],
            ['Adapter', $stream->getAdapter()],
            ['Status', $stream->getStatus()->value],
            ['Stream Key', $stream->getStreamKey()],
            ['Created', $stream->getCreatedAt()->format('Y-m-d H:i:s')],
            ['Updated', $stream->getUpdatedAt()->format('Y-m-d H:i:s')],
        ];

        if ($stream->getErrorMessage()) {
            $statusData[] = ['Error', $stream->getErrorMessage()];
        }

        $io->table(['Property', 'Value'], $statusData);

        return Command::SUCCESS;
    }
}
