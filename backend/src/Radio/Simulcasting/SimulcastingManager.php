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
        private readonly LoggerInterface $logger
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
            $videoErrors = $this->validateVideoFiles($station);
            if (!empty($videoErrors)) {
                // Try to create default video files
                throw new RuntimeException('Required video files missing and could not be created: ' . implode(', ', $videoErrors));
            }

            $this->logger->info('Starting simulcasting stream', [
                'station_id' => $station->id,
                'simulcasting_id' => $simulcasting->getId(),
                'adapter' => $simulcasting->getAdapter(),
            ]);

            // Update status to Starting
            $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Starting);
            $simulcasting->setErrorMessage(null);
            $this->em->persist($simulcasting);
            $this->em->flush();

            // Get the output name for this simulcasting stream
            $outputName = $this->getLiquidSoapOutputName($simulcasting);
            
            // Send start command to LiquidSoap via telnet API
            $result = $liquidsoap->command($station, "{$outputName}_start");
            
            if (!empty($result) && !str_contains(implode(' ', $result), 'error')) {
                
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
                $this->em->persist($simulcasting);
                $this->em->flush();
                
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
            $this->em->persist($simulcasting);
            $this->em->flush();

            // Get the output name for this simulcasting stream
            $outputName = $this->getLiquidSoapOutputName($simulcasting);
            
            // Send stop command to LiquidSoap via telnet API
            $result = $liquidsoap->command($station, "{$outputName}_stop");
            
            if (!empty($result) && !str_contains(implode(' ', $result), 'error')) {
                
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
                $this->em->persist($simulcasting);
                $this->em->flush();
                
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
                return "simulcast_facebook_{$cleanName}_{$streamId}";
            case 'youtube':
                return "simulcast_youtube_{$cleanName}_{$streamId}";
            default:
                return "simulcast_url_{$cleanName}_{$streamId}";
        }
    }

    public function validateAdapter(string $adapterName): bool
    {
        return isset(self::ADAPTER_MAP[$adapterName]);
    }

    /**
     * Stop all simulcast instances for a station
     */
    public function stopAllForStation(Station $station): void
    {
        $this->logger->info('Stopping all simulcast instances for station restart', [
            'station_id' => $station->id,
            'station_name' => $station->name,
        ]);

        // Use the repository to efficiently update all active simulcast instances
        $simulcastingRepo = $this->em->getRepository(\App\Entity\Simulcasting::class);
        $simulcastingRepo->stopAllForStation($station);

        $this->logger->info('All simulcast instances stopped for station restart', [
            'station_id' => $station->id,
            'station_name' => $station->name,
        ]);
    }

    /**
     * Validate that required video files exist for simulcasting
     */
    private function validateVideoFiles(Station $station): array
    {
        $errors = [];
        $simulcastStorageDir = '/var/azuracast/storage/simulcast';
        
        $requiredFiles = [
            'video.mp4' => $simulcastStorageDir . '/video.mp4',
            'font.ttf' => $simulcastStorageDir . '/font.ttf',
        ];
        
        foreach ($requiredFiles as $filename => $filepath) {
            if (!file_exists($filepath)) {
                $errors[] = "Required file missing: {$filename} (expected at: {$filepath})";
            }
        }
        
        return $errors;
    }
}
