<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

use App\Entity\Simulcasting;
use App\Entity\Station;
use App\Radio\Backend\Liquidsoap;
use Psr\Log\LoggerInterface;

final class LiquidSoapSimulcastingService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Liquidsoap $liquidsoap
    ) {
    }

    /**
     * Check if a simulcasting stream is currently running
     */
    public function isStreamRunning(Simulcasting $simulcasting, Station $station): bool
    {
        try {
            // Check if LiquidSoap is running
            if (!$this->liquidsoap->isRunning($station)) {
                return false;
            }

            // Get the actual stream status from LiquidSoap HTTP API
            $streamStatus = $this->getStreamStatusFromLiquidSoap($simulcasting, $station);
            return $streamStatus['is_connected'] && $streamStatus['is_streaming'];
        } catch (\Exception $e) {
            $this->logger->error('Error checking stream status', [
                'stream_id' => $simulcasting->getId(),
                'stream_name' => $simulcasting->getName(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get detailed status of a simulcasting stream from LiquidSoap
     */
    public function getStreamStatusFromLiquidSoap(Simulcasting $simulcasting, Station $station): array
    {
        try {
            $httpPort = $this->liquidsoap->getHttpApiPort($station);
            $apiKey = $station->adapter_api_key;
            
            // Get stream status from LiquidSoap HTTP API
            $response = $this->makeLiquidSoapApiCall(
                $httpPort,
                $apiKey,
                'status'
            );
            
            if (!$response) {
                return [
                    'is_connected' => false,
                    'is_streaming' => false,
                    'error' => 'Failed to connect to LiquidSoap API',
                    'details' => null
                ];
            }
            
            // Get the output name for this simulcasting stream
            $outputName = $this->getLiquidSoapOutputName($simulcasting);
            
            // Look for the specific output in LiquidSoap status
            $isConnected = false;
            $isStreaming = false;
            $errorDetails = null;
            
            // Check if the output exists and is active
            if (str_contains($response, $outputName)) {
                $isConnected = true;
                
                // Check if there are any errors
                if (str_contains($response, "error") || str_contains($response, "failed")) {
                    $isStreaming = false;
                    $errorDetails = "Stream has errors";
                } else {
                    $isStreaming = true;
                }
            }
            
            return [
                'is_connected' => $isConnected,
                'is_streaming' => $isStreaming,
                'error' => $errorDetails,
                'details' => $response
            ];
            
        } catch (\Exception $e) {
            return [
                'is_connected' => false,
                'is_streaming' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'details' => null
            ];
        }
    }

    /**
     * Get the LiquidSoap output name for a simulcasting stream
     * This must match the naming convention used in SimulcastingManager and ConfigWriter
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

    /**
     * Make an API call to LiquidSoap HTTP API
     */
    private function makeLiquidSoapApiCall(int $port, string $apiKey, string $command): ?string
    {
        try {
            $url = "http://localhost:{$port}/{$command}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Liquidsoap-Api-Key: {$apiKey}",
                "Content-Type: application/json"
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response !== false) {
                return $response;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to make LiquidSoap API call', [
                'port' => $port,
                'command' => $command,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the current status of all simulcasting streams for a station
     */
    public function getStreamsStatus(Station $station): array
    {
        $statuses = [];
        
        foreach ($station->simulcasting_streams as $stream) {
            $streamStatus = $this->getStreamStatusFromLiquidSoap($stream, $station);
            
            $statuses[] = [
                'id' => $stream->getId(),
                'name' => $stream->getName(),
                'adapter' => $stream->getAdapter(),
                'status' => $stream->getStatus()->value,
                'is_connected' => $streamStatus['is_connected'],
                'is_streaming' => $streamStatus['is_streaming'],
                'error_message' => $stream->getErrorMessage() ?: $streamStatus['error'],
                'liquidsoap_details' => $streamStatus['details']
            ];
        }
        
        return $statuses;
    }

    /**
     * Validate that required video files exist for simulcasting
     */
    public function validateVideoFiles(Station $station): array
    {
        $errors = [];
        $mediaDir = $station->media_storage_location->getFilteredPath();
        
        $requiredFiles = [
            'video.mp4' => $mediaDir . '/videostream/video.mp4',
            'font.ttf' => $mediaDir . '/videostream/font.ttf',
        ];
        
        foreach ($requiredFiles as $filename => $filepath) {
            if (!file_exists($filepath)) {
                $errors[] = "Required file missing: {$filename} (expected at: {$filepath})";
            }
        }
        
        return $errors;
    }

    /**
     * Create default video files for simulcasting if they don't exist
     */
    public function createDefaultVideoFiles(Station $station): bool
    {
        try {
            $mediaDir = $station->media_storage_location->getFilteredPath();
            $videoDir = $mediaDir . '/videostream';
            
            // Create videostream directory if it doesn't exist
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            // Create a simple black video file if it doesn't exist
            $videoFile = $videoDir . '/video.mp4';
            if (!file_exists($videoFile)) {
                $this->createBlackVideo($videoFile);
            }
            
            // Create a default font file if it doesn't exist
            $fontFile = $videoDir . '/font.ttf';
            if (!file_exists($fontFile)) {
                $this->createDefaultFont($fontFile);
            }
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create default video files', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a simple black video file using FFmpeg
     */
    private function createBlackVideo(string $outputPath): void
    {
        $command = [
            'ffmpeg',
            '-f', 'lavfi',
            '-i', 'color=black:size=1280x720:rate=30',
            '-t', '1',
            '-c:v', 'libx264',
            '-preset', 'ultrafast',
            '-y',
            $outputPath
        ];
        
        $process = new \Symfony\Component\Process\Process($command);
        $process->setTimeout(60);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Failed to create video file: ' . $process->getErrorOutput());
        }
    }

    /**
     * Create a default font file (copy from system or create placeholder)
     */
    private function createDefaultFont(string $outputPath): void
    {
        // Try to copy a system font
        $systemFonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/arial.ttf',
            '/System/Library/Fonts/Arial.ttf', // macOS
            'C:/Windows/Fonts/arial.ttf', // Windows
        ];
        
        foreach ($systemFonts as $systemFont) {
            if (file_exists($systemFont)) {
                copy($systemFont, $outputPath);
                return;
            }
        }
        
        // If no system font found, create a placeholder file
        file_put_contents($outputPath, '/* Placeholder font file - replace with actual TTF font */');
    }
}
