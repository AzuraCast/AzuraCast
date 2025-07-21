<?php

declare(strict_types=1);

namespace App\Radio\Backend;

use App\Entity\Api\LogType;
use App\Entity\Station;
use App\Entity\StationStreamer;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Exception;
use App\Nginx\CustomUrls;
use App\Radio\AbstractLocalAdapter;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use App\Radio\Configuration;
use App\Radio\Enums\LiquidsoapQueues;
use LogicException;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Process\Process;

final class Liquidsoap extends AbstractLocalAdapter
{
    public const string GLOBAL_CACHE_PATH = '/tmp/liquidsoap_cache';
    public const string USER_CACHE_DIR = '/liquidsoap_cache';

    /**
     * @inheritDoc
     */
    public function getConfigurationPath(Station $station): string
    {
        return $station->getRadioConfigDir() . '/liquidsoap.liq';
    }

    /**
     * @inheritDoc
     */
    public function getCurrentConfiguration(Station $station): string
    {
        $event = new WriteLiquidsoapConfiguration($station, false, true);
        $this->dispatcher->dispatch($event);

        return $event->buildConfiguration();
    }

    /**
     * Returns the internal port used to relay requests and other changes from AzuraCast to LiquidSoap.
     *
     * @param Station $station
     *
     * @return int The port number to use for this station.
     */
    public function getHttpApiPort(Station $station): int
    {
        $settings = $station->backend_config;
        return $settings->telnet_port ?? ($this->getStreamPort($station) - 1);
    }

    /**
     * Returns the port used for DJs/Streamers to connect to LiquidSoap for broadcasting.
     *
     * @param Station $station
     *
     * @return int The port number to use for this station.
     */
    public function getStreamPort(Station $station): int
    {
        $djPort = $station->backend_config->dj_port;
        if (null !== $djPort) {
            return $djPort;
        }

        // Default to frontend port + 5
        $frontendConfig = $station->frontend_config;
        $frontendPort = $frontendConfig->port ?? (8000 + (($station->id - 1) * 10));

        return $frontendPort + 5;
    }

    /**
     * Execute the specified remote command on LiquidSoap via the telnet API.
     *
     * @param Station $station
     * @param string $commandStr
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function command(Station $station, string $commandStr): array
    {
        $apiUri = $this->environment->getLocalUri()
            ->withPort($this->getHttpApiPort($station))
            ->withPath('/telnet');

        $response = $this->httpClient->post($apiUri, [
            'headers' => [
                'x-liquidsoap-api-key' => $station->adapter_api_key,
            ],
            'body' => $commandStr,
        ]);

        $responseBody = trim($response->getBody()->getContents());
        return explode("\n", $responseBody);
    }

    /**
     * @inheritdoc
     */
    public function getCommand(Station $station): string
    {
        $binary = $this->getBinary();

        return sprintf(
            '%s %s',
            escapeshellcmd($binary),
            escapeshellarg($this->getConfigurationPath($station))
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnvironmentVariables(Station $station): array
    {
        $tempDir = [
            'TMPDIR' => $station->getRadioTempDir(),
        ];

        if ($this->environment->isProduction()) {
            return [
                ...$tempDir,
                'LIQ_CACHE_SYSTEM_DIR' => self::GLOBAL_CACHE_PATH,
                'LIQ_CACHE_USER_DIR' => $this->environment->getTempDirectory() . self::USER_CACHE_DIR,
            ];
        }

        // Disable cache for dev/testing environments.
        return [
            ...$tempDir,
            'LIQ_CACHE' => 'false',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getBinary(): string
    {
        return '/usr/local/bin/liquidsoap';
    }

    public function getVersion(): ?string
    {
        $binary = $this->getBinary();

        $process = new Process([$binary, '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return preg_match('/^Liquidsoap (.+)$/im', $process->getOutput(), $matches)
            ? $matches[1]
            : null;
    }

    public function getHlsUrl(Station $station, ?UriInterface $baseUrl = null): UriInterface
    {
        $baseUrl ??= $this->router->getBaseUrl();
        return $baseUrl->withPath(
            $baseUrl->getPath() . CustomUrls::getHlsUrl($station) . '/live.m3u8'
        );
    }

    public function isQueueEmpty(
        Station $station,
        LiquidsoapQueues $queue
    ): bool {
        $queueResult = $this->command(
            $station,
            sprintf('%s.queue', $queue->value)
        );
        return empty($queueResult[0]);
    }

    /**
     * @return string[]
     */
    public function enqueue(
        Station $station,
        LiquidsoapQueues $queue,
        string $musicFile
    ): array {
        return $this->command(
            $station,
            sprintf('%s.push %s', $queue->value, $musicFile)
        );
    }

    /**
     * @return string[]
     */
    public function skip(Station $station): array
    {
        return $this->command(
            $station,
            'radio.skip'
        );
    }

    /**
     * @return string[]
     */
    public function updateMetadata(Station $station, array $newMeta): array
    {
        return $this->command(
            $station,
            'custom_metadata.insert ' . ConfigWriter::annotateArray($newMeta),
        );
    }

    /**
     * Tell LiquidSoap to disconnect the current live streamer.
     *
     * @param Station $station
     *
     * @return string[]
     */
    public function disconnectStreamer(Station $station): array
    {
        $currentStreamer = $station->current_streamer;
        $disconnectTimeout = $station->disconnect_deactivate_streamer;

        if ($currentStreamer instanceof StationStreamer && $disconnectTimeout > 0) {
            $currentStreamer->deactivateFor($disconnectTimeout);

            $this->em->persist($currentStreamer);
            $this->em->flush();
        }

        return $this->command(
            $station,
            'input_streamer.stop'
        );
    }

    public function getWebStreamingUrl(Station $station, UriInterface $baseUrl): UriInterface
    {
        $djMount = $station->backend_config->dj_mount_point;

        return $baseUrl
            ->withScheme('wss')
            ->withPath($baseUrl->getPath() . CustomUrls::getWebDjUrl($station) . $djMount);
    }

    public function verifyConfig(string $config): void
    {
        $binary = $this->getBinary();

        $process = new Process([
            $binary,
            '--check',
            '-',
        ]);

        $process->setInput($config);
        $process->run();

        if (1 === $process->getExitCode()) {
            throw new LogicException($process->getOutput());
        }
    }

    public function getSupervisorProgramName(Station $station): string
    {
        return Configuration::getSupervisorProgramName($station, 'backend');
    }

    public function getLogTypes(Station $station): array
    {
        $stationConfigDir = $station->getRadioConfigDir();

        return [
            new LogType(
                'liquidsoap_log',
                __('Liquidsoap Log'),
                $stationConfigDir . '/liquidsoap.log',
                true
            ),
            new LogType(
                'liquidsoap_liq',
                __('Liquidsoap Configuration'),
                $stationConfigDir . '/liquidsoap.liq',
                false
            ),
        ];
    }
}
