<?php
namespace App\Radio\Backend;

use App\Entity;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\EventDispatcher;
use App\Exception;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use App\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\UriInterface;
use Supervisor\Supervisor;

class Liquidsoap extends AbstractBackend
{
    protected Entity\Repository\StationStreamerRepository $streamerRepo;

    public function __construct(
        EntityManager $em,
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
     *
     * @return bool
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
        $settings = (array)$station->getBackendConfig();
        return (int)($settings['telnet_port'] ?? ($this->getStreamPort($station) - 1));
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
        $settings = (array)$station->getBackendConfig();

        if (!empty($settings['dj_port'])) {
            return (int)$settings['dj_port'];
        }

        // Default to frontend port + 5
        $frontend_config = (array)$station->getFrontendConfig();
        $frontend_port = $frontend_config['port'] ?? (8000 + (($station->getId() - 1) * 10));

        return $frontend_port + 5;
    }

    /**
     * Execute the specified remote command on LiquidSoap via the telnet API.
     *
     * @param Entity\Station $station
     * @param string $command_str
     *
     * @return array
     * @throws Exception
     */
    public function command(Entity\Station $station, $command_str): array
    {
        $fp = stream_socket_client('tcp://' . (Settings::getInstance()->isDocker() ? 'stations' : 'localhost') . ':' . $this->getTelnetPort($station),
            $errno, $errstr, 20);

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
     * @inheritdoc
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
        $requests_var = ConfigWriter::getVarName('requests', $station);

        $queue = $this->command($station, $requests_var . '.queue');
        return empty($queue[0]);
    }

    public function enqueue(Entity\Station $station, $music_file): array
    {
        $requests_var = ConfigWriter::getVarName('requests', $station);
        return $this->command($station, $requests_var . '.push ' . $music_file);
    }

    /**
     * Tell LiquidSoap to skip the currently playing song.
     *
     * @param Entity\Station $station
     *
     * @return array
     */
    public function skip(Entity\Station $station): array
    {


        return $this->command(
            $station,
            ConfigWriter::getVarName('requests_fallback', $station) . '.skip'
        );
    }

    /**
     * Tell LiquidSoap to disconnect the current live streamer.
     *
     * @param Entity\Station $station
     *
     * @return array
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
            ConfigWriter::getVarName('input_streamer', $station) . '.stop'
        );
    }

    public function authenticateStreamer(
        Entity\Station $station,
        string $user = '',
        string $pass = ''
    ): string {
        // Allow connections using the exact broadcast source password.
        $fe_config = (array)$station->getFrontendConfig();
        if (!empty($fe_config['source_pw']) && strcmp($fe_config['source_pw'], $pass) === 0) {
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
        $backendConfig = (array)$station->getBackendConfig();
        $recordStreams = (bool)($backendConfig['record_streams'] ?? false);

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

        $settings = (array)$station->getBackendConfig();
        $djMount = $settings['dj_mount_point'] ?? '/';

        return $base_url
            ->withScheme('wss')
            ->withPath($base_url->getPath() . '/radio/' . $stream_port . $djMount);
    }
}
