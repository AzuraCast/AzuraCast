<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity\Enums\PlaylistTypes;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Environment;
use App\Exception;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Supervisor\Exception\SupervisorException;
use Supervisor\SupervisorInterface;

final class Configuration
{
    public const DEFAULT_PORT_MIN = 8000;
    public const DEFAULT_PORT_MAX = 8499;
    public const PROTECTED_PORTS = [
        3306, // MariaDB
        6010, // Nginx internal
        6379, // Redis
        8080, // Common debug port
        80,   // HTTP
        443,  // HTTPS
        2022, // SFTP
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Adapters $adapters,
        private readonly SupervisorInterface $supervisor,
        private readonly Environment $environment,
        private readonly StationPlaylistMediaRepository $spmRepo,
    ) {
    }

    public function initializeConfiguration(Station $station): void
    {
        // Ensure default values for frontend/backend config exist.
        $station->setFrontendConfig($station->getFrontendConfig());
        $station->setBackendConfig($station->getBackendConfig());

        // Ensure port configuration exists
        $this->assignRadioPorts($station);

        // Clear station caches and generate API adapter key if none exists.
        if (empty($station->getAdapterApiKey())) {
            $station->generateAdapterApiKey();
        }

        // Ensure all directories exist.
        $station->ensureDirectoriesExist();

        // Check for at least one playlist, and create one if it doesn't exist.
        $defaultPlaylists = $station->getPlaylists()->filter(
            function (StationPlaylist $row) {
                return $row->getIsEnabled() && PlaylistTypes::default() === $row->getType();
            }
        );

        if (0 === $defaultPlaylists->count()) {
            $defaultPlaylist = new StationPlaylist($station);
            $defaultPlaylist->setName('default');
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
        $supervisorConfigFile = $this->getSupervisorConfigFile($station);

        $frontendEnum = $station->getFrontendType();
        $backendEnum = $station->getBackendType();

        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        // If no processes need to be managed, remove any existing config.
        if (
            (null === $frontend || !$frontend->hasCommand($station))
            && (null === $backend || !$backend->hasCommand($station))
        ) {
            $this->unlinkAndStopStation($station, $reloadSupervisor, true);
            throw new RuntimeException('Station has no local services.');
        }

        if (!$station->getHasStarted()) {
            $this->unlinkAndStopStation($station, $reloadSupervisor);
            throw new RuntimeException('Station has not started yet.');
        }

        if (!$station->getIsEnabled()) {
            $this->unlinkAndStopStation($station, $reloadSupervisor);
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
                'environment' => 'TZ="' . $station->getTimezone() . '"',
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
        $supervisor_config_data = implode("\n", $supervisorConfig);
        file_put_contents($supervisorConfigFile, $supervisor_config_data);

        // Write supporting configurations.
        $frontend?->write($station);
        $backend?->write($station);

        $this->markAsStarted($station);

        // Reload Supervisord and process groups
        if ($reloadSupervisor) {
            $affected_groups = $this->reloadSupervisor();
            $was_restarted = in_array($stationGroup, $affected_groups, true);

            if (!$was_restarted && $forceRestart) {
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

    private function getSupervisorConfigFile(Station $station): string
    {
        $configDir = $station->getRadioConfigDir();
        return $configDir . '/supervisord.conf';
    }

    private function unlinkAndStopStation(
        Station $station,
        bool $reloadSupervisor = true,
        bool $isRemoteOnly = false
    ): void {
        $station->setHasStarted($isRemoteOnly);
        $station->setNeedsRestart(false);
        $station->setCurrentStreamer(null);
        $station->setCurrentSong(null);

        $this->em->persist($station);
        $this->em->flush();

        $supervisorConfigFile = $this->getSupervisorConfigFile($station);
        @unlink($supervisorConfigFile);
        if ($reloadSupervisor) {
            $this->stopForStation($station);
        }
    }

    private function stopForStation(Station $station): void
    {
        $this->markAsStarted($station);

        $station_group = 'station_' . $station->getId();
        $affected_groups = $this->reloadSupervisor();

        if (!in_array($station_group, $affected_groups, true)) {
            try {
                $this->supervisor->stopProcessGroup($station_group, false);
            } catch (SupervisorException) {
            }
        }
    }

    private function markAsStarted(Station $station): void
    {
        $station->setHasStarted(true);
        $station->setNeedsRestart(false);
        $station->setCurrentStreamer(null);
        $station->setCurrentSong(null);

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
            $station->getFrontendType()->isEnabled()
            || $station->getBackendType()->isEnabled()
        ) {
            $frontend_config = $station->getFrontendConfig();
            $backend_config = $station->getBackendConfig();

            $base_port = $frontend_config->getPort();
            if ($force || null === $base_port) {
                $base_port = $this->getFirstAvailableRadioPort($station);

                $frontend_config->setPort($base_port);
                $station->setFrontendConfig($frontend_config);
            }

            $djPort = $backend_config->getDjPort();
            if ($force || null === $djPort) {
                $backend_config->setDjPort($base_port + 5);
                $station->setBackendConfig($backend_config);
            }

            $telnetPort = $backend_config->getTelnetPort();
            if ($force || null === $telnetPort) {
                $backend_config->setTelnetPort($base_port + 4);
                $station->setBackendConfig($backend_config);
            }

            $this->em->persist($station);
            $this->em->flush();
        }
    }

    /**
     * Determine the first available 10-port block that has no stations occupying it.
     */
    public function getFirstAvailableRadioPort(Station $station = null): int
    {
        $used_ports = $this->getUsedPorts($station);

        // Iterate from port 8000 to 9000, in increments of 10
        $protected_ports = self::PROTECTED_PORTS;

        $port_min = $this->environment->getAutoAssignPortMin();
        $port_max = $this->environment->getAutoAssignPortMax();

        for ($port = $port_min; $port <= $port_max; $port += 10) {
            if (in_array($port, $protected_ports, true)) {
                continue;
            }

            $range_in_use = false;
            for ($i = $port; $i < $port + 10; $i++) {
                if (isset($used_ports[$i])) {
                    $range_in_use = true;
                    break;
                }
            }

            if (!$range_in_use) {
                return $port;
            }
        }

        throw new Exception('This installation has no available ports for new radio stations.');
    }

    /**
     * Get an array of all used ports across the system, except the ones used by the station specified (if specified).
     */
    public function getUsedPorts(Station $except_station = null): array
    {
        static $used_ports;

        if (null === $used_ports) {
            $used_ports = [];

            // Get all station used ports.
            $station_configs = $this->em->createQuery(
                <<<'DQL'
                    SELECT s.id, s.name, s.frontend_type, s.frontend_config, s.backend_type, s.backend_config
                    FROM App\Entity\Station s
                DQL
            )->getArrayResult();

            foreach ($station_configs as $row) {
                $station_reference = ['id' => $row['id'], 'name' => $row['name']];

                if ($row['frontend_type'] !== FrontendAdapters::Remote->value) {
                    $frontend_config = (array)$row['frontend_config'];

                    if (!empty($frontend_config['port'])) {
                        $port = (int)$frontend_config['port'];
                        $used_ports[$port] = $station_reference;
                    }
                }

                if ($row['backend_type'] !== BackendAdapters::None->value) {
                    $backend_config = (array)$row['backend_config'];

                    // For DJ port, consider both the assigned port and port+1 to be reserved and in-use.
                    if (!empty($backend_config['dj_port'])) {
                        $port = (int)$backend_config['dj_port'];
                        $used_ports[$port] = $station_reference;
                        $used_ports[$port + 1] = $station_reference;
                    }
                    if (!empty($backend_config['telnet_port'])) {
                        $port = (int)$backend_config['telnet_port'];
                        $used_ports[$port] = $station_reference;
                    }
                }
            }
        }

        if (null !== $except_station && null !== $except_station->getId()) {
            return array_filter(
                $used_ports,
                static function ($station_reference) use ($except_station) {
                    return ($station_reference['id'] !== $except_station->getId());
                }
            );
        }

        return $used_ports;
    }

    /**
     * Remove configuration (i.e. prior to station removal) and trigger a Supervisor refresh.
     *
     * @param Station $station
     */
    public function removeConfiguration(Station $station): void
    {
        if (Environment::getInstance()->isTesting()) {
            return;
        }

        $station_group = 'station_' . $station->getId();

        // Try forcing the group to stop, but don't hard-fail if it doesn't.
        try {
            $this->supervisor->stopProcessGroup($station_group);
            $this->supervisor->removeProcessGroup($station_group);
        } catch (SupervisorException) {
        }

        $supervisor_config_path = $this->getSupervisorConfigFile($station);
        @unlink($supervisor_config_path);

        $this->reloadSupervisor();
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
        return 'station_' . $station->getIdRequired();
    }

    public static function getSupervisorProgramName(Station $station, string $category): string
    {
        return 'station_' . $station->getIdRequired() . '_' . $category;
    }
}
