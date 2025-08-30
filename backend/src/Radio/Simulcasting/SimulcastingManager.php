<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Simulcasting;
use App\Entity\Station;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SimulcastingManager
{
    use EntityManagerAwareTrait;

    private const ADAPTER_MAP = [
        'facebook' => FacebookSimulcastingAdapter::class,
        'youtube' => YouTubeSimulcastingAdapter::class,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LiquidSoapSimulcastingService $liquidsoapService
    ) {
    }

    public function getAdapter(Simulcasting $simulcasting): AbstractSimulcastingAdapter
    {
        $adapterName = $simulcasting->getAdapter();
        
        if (!isset(self::ADAPTER_MAP[$adapterName])) {
            throw new InvalidArgumentException("Unknown adapter: {$adapterName}");
        }

        $adapterClass = self::ADAPTER_MAP[$adapterName];
        return new $adapterClass($simulcasting);
    }

    public function getAvailableAdapters(): array
    {
        $adapters = [];
        
        foreach (self::ADAPTER_MAP as $key => $class) {
            $tempSimulcasting = new Simulcasting(
                new Station(),
                'temp',
                $key,
                'temp_key'
            );
            
            $adapter = new $class($tempSimulcasting);
            $adapters[$key] = [
                'name' => $adapter->getAdapterName(),
                'description' => $adapter->getAdapterDescription(),
            ];
        }
        
        return $adapters;
    }

    public function startSimulcasting(Simulcasting $simulcasting, Liquidsoap $liquidsoap): bool
    {
        try {
            $station = $simulcasting->getStation();
            
            if (BackendAdapters::Liquidsoap !== $station->backend_type) {
                throw new RuntimeException('Simulcasting only works with LiquidSoap backend');
            }

            $adapter = $this->getAdapter($simulcasting);
            
            if (!$adapter->isConfigurable()) {
                throw new RuntimeException('Adapter configuration is invalid');
            }

            // Validate that required video files exist
            $videoErrors = $this->liquidsoapService->validateVideoFiles($station);
            if (!empty($videoErrors)) {
                // Try to create default video files
                if (!$this->liquidsoapService->createDefaultVideoFiles($station)) {
                    throw new RuntimeException('Required video files missing and could not be created: ' . implode(', ', $videoErrors));
                }
            }

            $this->logger->info('Starting simulcasting stream', [
                'station_id' => $station->id,
                'simulcasting_id' => $simulcasting->getId(),
                'adapter' => $simulcasting->getAdapter(),
            ]);

            // Update status to Starting
            $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Starting);
            $simulcasting->setErrorMessage(null);
            $this->entityManager->flush();

            // Get the output name for this simulcasting stream
            $outputName = $this->getLiquidSoapOutputName($simulcasting);
            
            // Send start command to LiquidSoap via telnet API
            $result = $liquidsoap->command($station, "{$outputName}.start");
            
            if (!empty($result) && !str_contains(implode(' ', $result), 'error')) {
                // Success - update status to Running
                $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Running);
                $this->entityManager->flush();
                
                $this->logger->info('Simulcasting stream started successfully', [
                    'station_id' => $station->id,
                    'simulcasting_id' => $simulcasting->getId(),
                    'output_name' => $outputName,
                    'result' => $result,
                ]);
                
                return true;
            } else {
                // Failed to start
                $errorMsg = implode(' ', $result);
                $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Error);
                $simulcasting->setErrorMessage("Failed to start stream: {$errorMsg}");
                $this->entityManager->flush();
                
                $this->logger->error('Failed to start simulcasting stream', [
                    'station_id' => $station->id,
                    'simulcasting_id' => $simulcasting->getId(),
                    'output_name' => $outputName,
                    'result' => $result,
                ]);
                
                return false;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to start simulcasting', [
                'station_id' => $simulcasting->getStation()->id,
                'simulcasting_id' => $simulcasting->getId(),
                'error' => $e->getMessage(),
            ]);

            $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Error);
            $simulcasting->setErrorMessage($e->getMessage());
            
            return false;
        }
    }

    public function stopSimulcasting(Simulcasting $simulcasting, Liquidsoap $liquidsoap): bool
    {
        try {
            $station = $simulcasting->getStation();
            
            $this->logger->info('Stopping simulcasting stream', [
                'station_id' => $station->id,
                'simulcasting_id' => $simulcasting->getId(),
                'adapter' => $simulcasting->getAdapter(),
            ]);

            // Update status to Stopping
            $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Stopping);
            $this->entityManager->flush();

            // Get the output name for this simulcasting stream
            $outputName = $this->getLiquidSoapOutputName($simulcasting);
            
            // Send stop command to LiquidSoap via telnet API
            $result = $liquidsoap->command($station, "{$outputName}.stop");
            
            if (!empty($result) && !str_contains(implode(' ', $result), 'error')) {
                // Success - update status to Stopped
                $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Stopped);
                $this->entityManager->flush();
                
                $this->logger->info('Simulcasting stream stopped successfully', [
                    'station_id' => $station->id,
                    'simulcasting_id' => $simulcasting->getId(),
                    'output_name' => $outputName,
                    'result' => $result,
                ]);
                
                return true;
            } else {
                // Failed to stop
                $errorMsg = implode(' ', $result);
                $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Error);
                $simulcasting->setErrorMessage("Failed to stop stream: {$errorMsg}");
                $this->entityManager->flush();
                
                $this->logger->error('Failed to stop simulcasting stream', [
                    'station_id' => $station->id,
                    'simulcasting_id' => $simulcasting->getId(),
                    'output_name' => $outputName,
                    'result' => $result,
                ]);
                
                return false;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to stop simulcasting', [
                'station_id' => $simulcasting->getStation()->id,
                'simulcasting_id' => $simulcasting->getId(),
                'error' => $e->getMessage(),
            ]);

            $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Error);
            $simulcasting->setErrorMessage($e->getMessage());
            
            return false;
        }
    }

    /**
     * Get the LiquidSoap output name for a simulcasting stream
     * This must match the naming convention used in ConfigWriter
     */
    private function getLiquidSoapOutputName(Simulcasting $simulcasting): string
    {
        $adapter = $simulcasting->getAdapter();
        $streamName = $simulcasting->getName();
        $streamId = $simulcasting->getId();
        
        // Generate a unique output name based on adapter, stream name, and ID
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '_', $streamName);
        $cleanName = strtolower($cleanName);
        
        switch ($adapter) {
            case 'facebook':
                return "output.facebook_{$cleanName}_{$streamId}";
            case 'youtube':
                return "output.youtube_{$cleanName}_{$streamId}";
            default:
                return "output.url_{$cleanName}_{$streamId}";
        }
    }

    public function getStreamsStatus(Station $station): array
    {
        return $this->liquidsoapService->getStreamsStatus($station);
    }

    public function validateAdapter(string $adapterName): bool
    {
        return isset(self::ADAPTER_MAP[$adapterName]);
    }
}
