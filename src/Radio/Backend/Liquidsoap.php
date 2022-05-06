<?php

declare(strict_types=1);

namespace App\Radio\Backend;

use App\Entity;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Exception;
use App\Radio\Enums\LiquidsoapQueues;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Process\Process;

class Liquidsoap extends AbstractBackend
{
    public function supportsMedia(): bool
    {
        return true;
    }

    public function supportsRequests(): bool
    {
        return true;
    }

    public function supportsStreamers(): bool
    {
        return true;
    }

    public function supportsWebStreaming(): bool
    {
        return true;
    }

    public function supportsImmediateQueue(): bool
    {
        return true;
    }

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
     * Assemble a list of annotations for LiquidSoap.
     *
     * Liquidsoap expects a string similar to:
     *     annotate:type="song",album="$ALBUM",display_desc="$FULLSHOWNAME",
     *     liq_start_next="2.5",liq_fade_in="3.5",liq_fade_out="3.5":$SONGPATH
     *
     * @param Entity\StationMedia $media
     *
     * @return mixed[]
     */
    public function annotateMedia(Entity\StationMedia $media): array
    {
        $annotations = [];
        $annotation_types = [
            'title' => $media->getTitle(),
            'artist' => $media->getArtist(),
            'duration' => $media->getLength(),
            'song_id' => $media->getSongId(),
            'media_id' => $media->getId(),
            'liq_amplify' => $media->getAmplify() ?? 0.0,
            'liq_cross_duration' => $media->getFadeOverlap(),
            'liq_fade_in' => $media->getFadeIn(),
            'liq_fade_out' => $media->getFadeOut(),
            'liq_cue_in' => $media->getCueIn(),
            'liq_cue_out' => $media->getCueOut(),
        ];

        // Safety checks for cue lengths.
        if ($annotation_types['liq_cue_out'] < 0) {
            $cue_out = abs($annotation_types['liq_cue_out']);
            if (0.0 === $cue_out || $cue_out > $annotation_types['duration']) {
                $annotation_types['liq_cue_out'] = null;
            } else {
                $annotation_types['liq_cue_out'] = max(0, $annotation_types['duration'] - $cue_out);
            }
        }
        if ($annotation_types['liq_cue_out'] > $annotation_types['duration']) {
            $annotation_types['liq_cue_out'] = null;
        }
        if ($annotation_types['liq_cue_in'] > $annotation_types['duration']) {
            $annotation_types['liq_cue_in'] = null;
        }

        foreach ($annotation_types as $annotation_name => $prop) {
            if (null === $prop) {
                continue;
            }

            $prop = self::annotateString((string)$prop);

            // Convert Liquidsoap-specific annotations to floats.
            if ('duration' === $annotation_name || str_starts_with($annotation_name, 'liq')) {
                $prop = Liquidsoap\ConfigWriter::toFloat($prop);
            }

            if ('liq_amplify' === $annotation_name) {
                $prop .= 'dB';
            }

            $annotations[$annotation_name] = $prop;
        }

        return $annotations;
    }

    public static function annotateString(string $str): string
    {
        $str = mb_convert_encoding($str, 'UTF-8');
        return str_replace(['"', "\n", "\t", "\r"], ['\"', '', '', ''], $str);
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
            'interrupting_fallback.skip'
        );
    }

    /**
     * @return string[]
     */
    public function updateMetadata(Entity\Station $station, array $newMeta): array
    {
        $metaStr = [];
        foreach ($newMeta as $metaKey => $metaVal) {
            $metaStr[] = $metaKey . '="' . self::annotateString($metaVal) . '"';
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
        $stream_port = $this->getStreamPort($station);

        $djMount = $station->getBackendConfig()->getDjMountPoint();

        return $base_url
            ->withScheme('wss')
            ->withPath($base_url->getPath() . '/radio/' . $stream_port . $djMount);
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
            throw new \LogicException($process->getOutput());
        }
    }
}
