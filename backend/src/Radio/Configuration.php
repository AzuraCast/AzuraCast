<?php

declare(strict_types=1);

namespace App\Radio;

use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Exception;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use RuntimeException;
use Supervisor\Exception\SupervisorException;
use Supervisor\SupervisorInterface;

final class Configuration
{
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;

    public const int DEFAULT_PORT_MIN = 8000;
    public const int DEFAULT_PORT_MAX = 8499;

    public const string PER_STATION_SUPERVISOR_CONF = 'supervisord.conf';

    public const array PROTECTED_PORTS = [
        3306, // MariaDB
        6010, // Nginx internal
        6379, // Redis
        8080, // Common debug port
        80,   // HTTP
        443,  // HTTPS
        2022, // SFTP
    ];

    public function __construct(
        private readonly Adapters $adapters,
        private readonly SupervisorInterface $supervisor,
        private readonly StationPlaylistMediaRepository $spmRepo,
    ) {
    }

    public function initializeConfiguration(Station $station): void
    {
        // Ensure port configuration exists
        $this->assignRadioPorts($station);

        // Clear station caches and generate API adapter key if none exists.
        if (empty($station->adapter_api_key)) {
            $station->generateAdapterApiKey();
        }

        // Ensure all directories exist.
        $station->ensureDirectoriesExist();

        // Check for at least one playlist, and create one if it doesn't exist.
        $defaultPlaylists = $station->playlists->filter(
            function (StationPlaylist $row) {
                return $row->is_enabled && PlaylistTypes::default() === $row->type;
            }
        );

        if (0 === $defaultPlaylists->count()) {
            $defaultPlaylist = new StationPlaylist($station);
            $defaultPlaylist->name = 'default';
            $this->em->persist($defaultPlaylist);
        }

        $this->em->persist($station);
        foreach ($station->getAllStorageLocations() as $storageLocation) {
            $this->em->persist($storageLocation);
        }

        $this->em->flush();

        $this->spmRepo->resetAllQueues($station);
    }

    /**
     * Write all configuration changes to the filesystem and reload supervisord.
     */
    public function writeConfiguration(
        Station $station,
        bool $reloadSupervisor = true,
        bool $forceRestart = false,
        bool $attemptReload = true
    ): void {
        if ($this->environment->isTesting()) {
            return;
        }

        $this->initializeConfiguration($station);

        // Initialize adapters.
        $supervisorConfig = [];
        $supervisorConfigFile = self::getSupervisorConfPath($station);

        $frontendEnum = $station->frontend_type;
        $backendEnum = $station->backend_type;

        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        // If no processes need to be managed, remove any existing config.
        if (
            (null === $frontend || !$frontend->hasCommand($station))
            && (null === $backend || !$backend->hasCommand($station))
        ) {
            $this->removeLocalServices($station, $reloadSupervisor, true);
            throw new RuntimeException('Station has no local services.');
        }

        if (!$station->has_started) {
            $this->removeLocalServices($station, $reloadSupervisor);
            throw new RuntimeException('Station has not started yet.');
        }

        if (!$station->is_enabled) {
            $this->removeLocalServices($station, $reloadSupervisor);
            throw new RuntimeException('Station is disabled.');
        }

        // Write group section of config
        $programNames = [];
        $programs = [];

        if (null !== $backend && $backend->hasCommand($station)) {
            $programName = $backend->getSupervisorProgramName($station);

            $programs[$programName] = $backend;
            $programNames[] = $programName;
        }

        if (null !== $frontend && $frontend->hasCommand($station)) {
            $programName = $frontend->getSupervisorProgramName($station);

            $programs[$programName] = $frontend;
            $programNames[] = $programName;
        }

        $stationGroup = self::getSupervisorGroupName($station);

        $supervisorConfig[] = '[group:' . $stationGroup . ']';
        $supervisorConfig[] = 'programs=' . implode(',', $programNames);
        $supervisorConfig[] = '';

        foreach ($programs as $programName => $adapter) {
            $configLines = [
                'user' => 'azuracast',
                'priority' => 950,
                'startsecs' => 10,
                'startretries' => 5,
                'command' => $adapter->getCommand($station),
                'directory' => $station->getRadioConfigDir(),
                'environment' => self::buildEnvironment([
                    'TZ' => $station->timezone ?? 'UTC',
                    ...$adapter->getEnvironmentVariables($station),
                ]),
                'autostart' => 'false',
                'autorestart' => 'true',
                'stdout_logfile' => $adapter->getLogPath($station),
                'stdout_logfile_maxbytes' => '5MB',
                'stdout_logfile_backups' => '5',
                'redirect_stderr' => 'true',
                'stdout_events_enabled' => 'true',
                'stderr_events_enabled' => 'true',
            ];

            $supervisorConfig[] = '[program:' . $programName . ']';
            foreach ($configLines as $configKey => $configValue) {
                $supervisorConfig[] = $configKey . '=' . $configValue;
            }
            $supervisorConfig[] = '';
        }

        // Write config contents
        $supervisorConfigData = implode("\n", $supervisorConfig);
        file_put_contents($supervisorConfigFile, $supervisorConfigData);

        // Write supporting configurations.
        $frontend?->write($station);
        $backend?->write($station);

        $this->markAsStarted($station);

        // Reload Supervisord and process groups
        if ($reloadSupervisor) {
            $affectedGroups = $this->reloadSupervisor();
            $wasRestarted = in_array($stationGroup, $affectedGroups, true);

            if (!$wasRestarted && $forceRestart) {
                try {
                    if ($attemptReload && ($backendEnum->isEnabled() || $frontendEnum->supportsReload())) {
                        $backend?->reload($station);
                        $frontend?->reload($station);
                    } else {
                        $this->supervisor->stopProcessGroup($stationGroup);
                        $this->supervisor->startProcessGroup($stationGroup);
                    }
                } catch (SupervisorException) {
                }
            }
        }
    }

    private function removeLocalServices(
        Station $station,
        bool $reloadSupervisor = true,
        bool $isRemoteOnly = false
    ): void {
        $station->has_started = $isRemoteOnly;
        $station->needs_restart = false;
        $station->current_streamer = null;
        $station->current_song = null;

        $this->em->persist($station);
        $this->em->flush();

        $this->removeConfiguration($station, $reloadSupervisor);
    }

    private function markAsStarted(Station $station): void
    {
        $station->has_started = true;
        $station->needs_restart = false;
        $station->current_streamer = null;
        $station->current_song = null;

        $this->em->persist($station);
        $this->em->flush();
    }

    /**
     * Trigger a supervisord reload and restart all relevant services.
     */
    private function reloadSupervisor(): array
    {
        return $this->supervisor->reloadAndApplyConfig()->getAffected();
    }

    /**
     * Assign the first available port range to this station, or ensure it already is configured properly.
     */
    public function assignRadioPorts(Station $station, bool $force = false): void
    {
        if (
            $station->frontend_type->isEnabled()
            || $station->backend_type->isEnabled()
        ) {
            $frontendConfig = $station->frontend_config;
            $backendConfig = $station->backend_config;

            $basePort = $frontendConfig->port;
            if ($force || null === $basePort) {
                $basePort = $this->getFirstAvailableRadioPort($station);

                $frontendConfig->port = $basePort;
                $station->frontend_config = $frontendConfig;
            }

            $djPort = $backendConfig->dj_port;
            if ($force || null === $djPort) {
                $backendConfig->dj_port = $basePort + 5;
                $station->backend_config = $backendConfig;
            }

            $telnetPort = $backendConfig->telnet_port;
            if ($force || null === $telnetPort) {
                $backendConfig->telnet_port = $basePort + 4;
                $station->backend_config = $backendConfig;
            }

            $this->em->persist($station);
        }
    }

    /**
     * Determine the first available 10-port block that has no stations occupying it.
     */
    public function getFirstAvailableRadioPort(?Station $station = null): int
    {
        $usedPorts = $this->getUsedPorts($station);

        // Iterate from port 8000 to 9000, in increments of 10
        $protectedPorts = self::PROTECTED_PORTS;

        $portMin = $this->environment->getAutoAssignPortMin();
        $portMax = $this->environment->getAutoAssignPortMax();

        for ($port = $portMin; $port <= $portMax; $port += 10) {
            if (in_array($port, $protectedPorts, true)) {
                continue;
            }

            $rangeInUse = false;
            for ($i = $port; $i < $port + 10; $i++) {
                if (isset($usedPorts[$i])) {
                    $rangeInUse = true;
                    break;
                }
            }

            if (!$rangeInUse) {
                return $port;
            }
        }

        throw new Exception('This installation has no available ports for new radio stations.');
    }

    /**
     * Get an array of all used ports across the system, except the ones used by the station specified (if specified).
     *
     * @return array<int, array{
     *   id: int,
     *   name: string
     * }>
     */
    public function getUsedPorts(?Station $exceptStation = null): array
    {
        static $usedPorts;

        if (null === $usedPorts) {
            $usedPorts = [];

            // Get all station used ports.
            $stationConfigs = $this->em->createQuery(
                <<<'DQL'
                    SELECT s.id, s.name, s.frontend_type, s.frontend_config_raw AS frontend_config,
                        s.backend_type, s.backend_config_raw AS backend_config
                    FROM App\Entity\Station s
                DQL
            )->getArrayResult();

            /** @var array<array-key, int|string|array> $row */
            foreach ($stationConfigs as $row) {
                $stationReference = ['id' => $row['id'], 'name' => $row['name']];

                if ($row['frontend_type'] !== FrontendAdapters::Remote->value) {
                    $frontendConfig = (array)$row['frontend_config'];

                    if (!empty($frontendConfig['port'])) {
                        $port = (int)$frontendConfig['port'];
                        $usedPorts[$port] = $stationReference;
                    }
                }

                if ($row['backend_type'] !== BackendAdapters::None->value) {
                    $backendConfig = (array)$row['backend_config'];

                    // For DJ port, consider both the assigned port and port+1 to be reserved and in-use.
                    if (!empty($backendConfig['dj_port'])) {
                        $port = (int)$backendConfig['dj_port'];
                        $usedPorts[$port] = $stationReference;
                        $usedPorts[$port + 1] = $stationReference;
                    }
                    if (!empty($backendConfig['telnet_port'])) {
                        $port = (int)$backendConfig['telnet_port'];
                        $usedPorts[$port] = $stationReference;
                    }
                }
            }
        }

        if (null !== $exceptStation && isset($exceptStation->id)) {
            return array_filter(
                $usedPorts,
                static function ($stationReference) use ($exceptStation) {
                    return ($stationReference['id'] !== $exceptStation->id);
                }
            );
        }

        return $usedPorts;
    }

    /**
     * Remove configuration (i.e. prior to station removal) and trigger a Supervisor refresh.
     *
     * @param Station $station
     */
    public function removeConfiguration(
        Station $station,
        bool $reloadSupervisor = true
    ): void {
        if ($this->environment->isTesting()) {
            return;
        }

        $stationGroup = 'station_' . $station->id;

        // Try forcing the group to stop, but don't hard-fail if it doesn't.
        if ($reloadSupervisor) {
            try {
                $this->supervisor->stopProcessGroup($stationGroup);
                $this->supervisor->removeProcessGroup($stationGroup);
            } catch (SupervisorException) {
            }
        }

        $supervisorConfigPath = self::getSupervisorConfPath($station);
        @unlink($supervisorConfigPath);

        if ($reloadSupervisor) {
            $this->reloadSupervisor();
        }
    }

    protected static function buildEnvironment(array $values): string
    {
        return implode(
            ',',
            array_map(
                static fn(string $k, mixed $v) => sprintf(
                    '%s="%s"',
                    $k,
                    str_replace('%', '%%', $v)
                ),
                array_keys($values),
                array_values($values)
            )
        );
    }

    public static function getSupervisorConfPath(
        Station|string $configDir
    ): string {
        if ($configDir instanceof Station) {
            $configDir = $configDir->getRadioConfigDir();
        }

        return $configDir . '/' . self::PER_STATION_SUPERVISOR_CONF;
    }

    /**
     * @return int[]
     */
    public static function enumerateDefaultPorts(
        int $rangeMin = self::DEFAULT_PORT_MIN,
        int $rangeMax = self::DEFAULT_PORT_MAX,
    ): array {
        $defaultPorts = [];

        for ($i = $rangeMin; $i < $rangeMax; $i += 10) {
            if (in_array($i, self::PROTECTED_PORTS, true)) {
                continue;
            }

            $defaultPorts[] = $i;
            $defaultPorts[] = $i + 5;
            $defaultPorts[] = $i + 6;
        }

        return $defaultPorts;
    }

    public static function getSupervisorGroupName(Station $station): string
    {
        return 'station_' . $station->id;
    }

    public static function getSupervisorProgramName(Station $station, string $category): string
    {
        return 'station_' . $station->id . '_' . $category;
    }
}
