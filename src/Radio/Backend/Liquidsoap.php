<?php

declare(strict_types=1);

namespace App\Radio\Backend;

use App\Entity;
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
    /**
     * @inheritDoc
     */
    public function getConfigurationPath(Entity\Station $station): ?string
    {
        return $station->getRadioConfigDir() . '/liquidsoap.liq';
    }

    /**
     * @inheritDoc
     */
    public function getCurrentConfiguration(Entity\Station $station): ?string
    {
        $event = new WriteLiquidsoapConfiguration($station, false, true);
        $this->dispatcher->dispatch($event);

        return $event->buildConfiguration();
    }

    /**
     * Returns the port used for DJs/Streamers to connect to LiquidSoap for broadcasting.
     *
     * @param Entity\Station $station
     *
     * @return int The port number to use for this station.
     */
    public function getStreamPort(Entity\Station $station): int
    {
        $djPort = $station->getBackendConfig()->getDjPort();
        if (null !== $djPort) {
            return $djPort;
        }

        // Default to frontend port + 5
        $frontend_config = $station->getFrontendConfig();
        $frontend_port = $frontend_config->getPort() ?? (8000 + (($station->getId() - 1) * 10));

        return $frontend_port + 5;
    }

    /**
     * Execute the specified remote command on LiquidSoap via the telnet API.
     *
     * @param Entity\Station $station
     * @param string $command_str
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function command(Entity\Station $station, string $command_str): array
    {
        $socketPath = 'unix://' . $station->getRadioConfigDir() . '/liquidsoap.sock';

        $fp = stream_socket_client(
            $socketPath,
            $errno,
            $errstr,
            20
        );

        if (!$fp) {
            throw new Exception('Telnet failure: ' . $errstr . ' (' . $errno . ')');
        }

        fwrite($fp, str_replace(["\\'", '&amp;'], ["'", '&'], urldecode($command_str)) . "\nquit\n");

        $response = [];
        while (!feof($fp)) {
            $response[] = trim(fgets($fp, 1024) ?: '');
        }

        fclose($fp);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(Entity\Station $station): ?string
    {
        if ($binary = $this->getBinary()) {
            $config_path = $station->getRadioConfigDir() . '/liquidsoap.liq';
            return $binary . ' ' . $config_path;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getBinary(): ?string
    {
        return '/usr/local/bin/liquidsoap';
    }

    public function getVersion(): ?string
    {
        $binary = $this->getBinary();
        if (null === $binary) {
            return null;
        }

        $process = new Process([$binary, '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return preg_match('/^Liquidsoap (.+)$/im', $process->getOutput(), $matches)
            ? $matches[1]
            : null;
    }

    public function getHlsUrl(Entity\Station $station, UriInterface $baseUrl = null): UriInterface
    {
        $baseUrl ??= $this->router->getBaseUrl();
        return $baseUrl->withPath(
            $baseUrl->getPath() . CustomUrls::getHlsUrl($station) . '/live.m3u8'
        );
    }

    public function isQueueEmpty(
        Entity\Station $station,
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
        Entity\Station $station,
        LiquidsoapQueues $queue,
        string $music_file
    ): array {
        return $this->command(
            $station,
            sprintf('%s.push %s', $queue->value, $music_file)
        );
    }

    /**
     * @return string[]
     */
    public function skip(Entity\Station $station): array
    {
        return $this->command(
            $station,
            'radio.skip'
        );
    }

    /**
     * @return string[]
     */
    public function updateMetadata(Entity\Station $station, array $newMeta): array
    {
        $metaStr = [];
        foreach ($newMeta as $metaKey => $metaVal) {
            $metaStr[] = $metaKey . '="' . ConfigWriter::annotateString($metaVal) . '"';
        }

        return $this->command(
            $station,
            'custom_metadata.insert ' . implode(',', $metaStr),
        );
    }

    /**
     * Tell LiquidSoap to disconnect the current live streamer.
     *
     * @param Entity\Station $station
     *
     * @return string[]
     */
    public function disconnectStreamer(Entity\Station $station): array
    {
        $current_streamer = $station->getCurrentStreamer();
        $disconnect_timeout = $station->getDisconnectDeactivateStreamer();

        if ($current_streamer instanceof Entity\StationStreamer && $disconnect_timeout > 0) {
            $current_streamer->deactivateFor($disconnect_timeout);

            $this->em->persist($current_streamer);
            $this->em->flush();
        }

        return $this->command(
            $station,
            'input_streamer.stop'
        );
    }

    public function getWebStreamingUrl(Entity\Station $station, UriInterface $base_url): UriInterface
    {
        $djMount = $station->getBackendConfig()->getDjMountPoint();

        return $base_url
            ->withScheme('wss')
            ->withPath($base_url->getPath() . CustomUrls::getWebDjUrl($station) . $djMount);
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

    public function getSupervisorProgramName(Entity\Station $station): string
    {
        return Configuration::getSupervisorProgramName($station, 'backend');
    }
}
