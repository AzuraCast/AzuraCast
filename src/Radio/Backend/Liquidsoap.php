<?php

namespace App\Radio\Backend;

use App\Entity;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\EventDispatcher;
use App\Exception;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\UriInterface;
use Supervisor\Supervisor;

class Liquidsoap extends AbstractBackend
{
    protected Entity\Repository\StationStreamerRepository $streamerRepo;

    public function __construct(
        EntityManagerInterface $em,
        Supervisor $supervisor,
        EventDispatcher $dispatcher,
        Entity\Repository\StationStreamerRepository $streamerRepo
    ) {
        parent::__construct($em, $supervisor, $dispatcher);

        $this->streamerRepo = $streamerRepo;
    }

    /**
     * Write configuration from Station object to the external service.
     *
     * Special thanks to the team of PonyvilleFM for assisting with Liquidsoap configuration and debugging.
     *
     * @param Entity\Station $station
     */
    public function write(Entity\Station $station): bool
    {
        $event = new WriteLiquidsoapConfiguration($station);
        $this->dispatcher->dispatch($event);

        $ls_config_contents = $event->buildConfiguration();

        $config_path = $station->getRadioConfigDir();
        $ls_config_path = $config_path . '/liquidsoap.liq';

        file_put_contents($ls_config_path, $ls_config_contents);
        return true;
    }

    public function getEditableConfiguration(Entity\Station $station): string
    {
        $event = new WriteLiquidsoapConfiguration($station, true);
        $this->dispatcher->dispatch($event);

        return $event->buildConfiguration();
    }

    /**
     * Returns the internal port used to relay requests and other changes from AzuraCast to LiquidSoap.
     *
     * @param Entity\Station $station
     *
     * @return int The port number to use for this station.
     */
    public function getTelnetPort(Entity\Station $station): int
    {
        $settings = $station->getBackendConfig();
        return $settings->getTelnetPort() ?? ($this->getStreamPort($station) - 1);
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
        $settings = $station->getBackendConfig();

        $djPort = $settings->getDjPort();
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
        if (($annotation_types['liq_cue_in'] + $annotation_types['liq_cue_out']) > $annotation_types['duration']) {
            $annotation_types['liq_cue_out'] = null;
        }
        if ($annotation_types['liq_cue_in'] > $annotation_types['duration']) {
            $annotation_types['liq_cue_in'] = null;
        }

        foreach ($annotation_types as $annotation_name => $prop) {
            if (null === $prop) {
                continue;
            }

            $prop = self::annotateString($prop);

            // Convert Liquidsoap-specific annotations to floats.
            if ('duration' === $annotation_name || 0 === strpos($annotation_name, 'liq')) {
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
        return str_replace(['"', "\n", "\t", "\r", '|'], ["'", '', '', '', '-'], $str);
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
    public function command(Entity\Station $station, $command_str): array
    {
        $hostname = (Settings::getInstance()->isDocker() ? 'stations' : 'localhost');
        $fp = stream_socket_client(
            'tcp://' . $hostname . ':' . $this->getTelnetPort($station),
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
            $response[] = trim(fgets($fp, 1024));
        }

        fclose($fp);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(Entity\Station $station): ?string
    {
        if ($binary = self::getBinary()) {
            $config_path = $station->getRadioConfigDir() . '/liquidsoap.liq';
            return $binary . ' ' . $config_path;
        }

        return '/bin/false';
    }

    /**
     * @inheritDoc
     */
    public static function getBinary()
    {
        // Docker revisions 3 and later use the `radio` container.
        $settings = Settings::getInstance();

        if ($settings->isDocker() && $settings[Settings::DOCKER_REVISION] < 3) {
            return '/var/azuracast/.opam/system/bin/liquidsoap';
        }

        return '/usr/local/bin/liquidsoap';
    }

    public function isQueueEmpty(Entity\Station $station): bool
    {
        $queue = $this->command(
            $station,
            ConfigWriter::getVarName($station, 'requests') . '.queue'
        );
        return empty($queue[0]);
    }

    /**
     * @return string[]
     */
    public function enqueue(Entity\Station $station, $music_file): array
    {
        return $this->command(
            $station,
            ConfigWriter::getVarName($station, 'requests') . '.push ' . $music_file
        );
    }

    /**
     * @return string[]
     */
    public function skip(Entity\Station $station): array
    {
        return $this->command(
            $station,
            ConfigWriter::getVarName($station, 'requests_fallback') . '.skip'
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
            ConfigWriter::getVarName($station, 'input_streamer') . '.stop'
        );
    }

    public function authenticateStreamer(
        Entity\Station $station,
        string $user = '',
        string $pass = ''
    ): string {
        // Allow connections using the exact broadcast source password.
        $fe_config = $station->getFrontendConfig();
        $sourcePw = $fe_config->getSourcePassword();
        if (!empty($sourcePw) && strcmp($sourcePw, $pass) === 0) {
            return 'true';
        }

        return $this->streamerRepo->authenticate($station, $user, $pass)
            ? 'true'
            : 'false';
    }

    public function onConnect(
        Entity\Station $station,
        string $user = ''
    ): string {
        $resp = $this->streamerRepo->onConnect($station, $user);

        if (is_string($resp)) {
            $this->command($station, 'recording.start ' . $resp);
            return 'recording';
        }

        return $resp ? 'true' : 'false';
    }

    public function onDisconnect(
        Entity\Station $station,
        string $user = ''
    ): string {
        $backendConfig = $station->getBackendConfig();
        $recordStreams = $backendConfig->recordStreams();

        if ($recordStreams) {
            $this->command($station, 'recording.stop');
        }

        return $this->streamerRepo->onDisconnect($station)
            ? 'true'
            : 'false';
    }

    public function getWebStreamingUrl(Entity\Station $station, UriInterface $base_url): UriInterface
    {
        $stream_port = $this->getStreamPort($station);

        $settings = $station->getBackendConfig();
        $djMount = $settings->getDjMountPoint();

        return $base_url
            ->withScheme('wss')
            ->withPath($base_url->getPath() . '/radio/' . $stream_port . $djMount);
    }
}
