<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Environment;
use App\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Supervisor\Exception\SupervisorException;
use Supervisor\Supervisor;

class Configuration
{
    public const DEFAULT_PORT_MIN = 8000;
    public const DEFAULT_PORT_MAX = 8499;
    public const PROTECTED_PORTS = [8080, 80, 443, 2022];

    public function __construct(
        protected EntityManagerInterface $em,
        protected Adapters $adapters,
        protected Supervisor $supervisor,
        protected Logger $logger,
        protected Environment $environment
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
                return $row->getIsEnabled() && StationPlaylist::TYPE_DEFAULT === $row->getType();
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
    }

    /**
     * Write all configuration changes to the filesystem and reload supervisord.
     *
     * @param Station $station
     * @param bool $forceRestart Always restart this station's supervisor instances, even if nothing changed.
     */
    public function writeConfiguration(
        Station $station,
        bool $forceRestart = false
    ): void {
        if ($this->environment->isTesting()) {
            return;
        }

        $this->initializeConfiguration($station);

        // Initialize adapters.
        $supervisorConfig = [];
        $supervisorConfigFile = $this->getSupervisorConfigFile($station);

        if (!$station->isEnabled()) {
            @unlink($supervisorConfigFile);
            $this->reloadSupervisorForStation($station);
            return;
        }

        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        // If no processes need to be managed, remove any existing config.
        if (!$frontend->hasCommand($station) && !$backend->hasCommand($station)) {
            @unlink($supervisorConfigFile);
            $this->reloadSupervisorForStation($station);
            return;
        }

        // Get group information
        $backend_name = $backend->getProgramName($station);
        [$backend_group, $backend_program] = explode(':', $backend_name);

        $frontend_name = $frontend->getProgramName($station);
        [, $frontend_program] = explode(':', $frontend_name);

        // Write group section of config
        $programs = [];
        if ($backend->hasCommand($station)) {
            $programs[] = $backend_program;
        }
        if ($frontend->hasCommand($station)) {
            $programs[] = $frontend_program;
        }

        $supervisorConfig[] = '[group:' . $backend_group . ']';
        $supervisorConfig[] = 'programs=' . implode(',', $programs);
        $supervisorConfig[] = '';

        // Write frontend
        if ($frontend->hasCommand($station)) {
            $supervisorConfig[] = $this->writeConfigurationSection($station, $frontend, 90);
        }

        // Write backend
        if ($backend->hasCommand($station)) {
            $supervisorConfig[] = $this->writeConfigurationSection($station, $backend, 100);
        }

        // Write config contents
        $supervisor_config_data = implode("\n", $supervisorConfig);
        file_put_contents($supervisorConfigFile, $supervisor_config_data);

        // Write supporting configurations.
        $frontend->write($station);
        $backend->write($station);

        $this->reloadSupervisorForStation($station, $forceRestart);
    }

    /**
     * @param Station $station
     */
    protected function getSupervisorConfigFile(Station $station): string
    {
        $configDir = $station->getRadioConfigDir();
        return $configDir . '/supervisord.conf';
    }

    /**
     * Trigger a supervisord reload/restart for a station, optionally forcing a restart of the station's
     * service group.
     *
     * @param Station $station
     * @param bool $force_restart
     */
    protected function reloadSupervisorForStation(Station $station, bool $force_restart = false): bool
    {
        $station_group = 'station_' . $station->getId();
        $affected_groups = $this->reloadSupervisor();

        $was_restarted = in_array($station_group, $affected_groups, true);

        if (!$was_restarted && $force_restart) {
            try {
                $this->supervisor->stopProcessGroup($station_group, true);
                $this->supervisor->startProcessGroup($station_group, true);
            } catch (SupervisorException) {
            }

            $was_restarted = true;
        }

        if ($was_restarted) {
            $station->setHasStarted(true);
            $station->setNeedsRestart(false);
            $station->clearCache();

            $this->em->persist($station);
            $this->em->flush();
        }

        return $was_restarted;
    }

    /**
     * Trigger a supervisord reload and restart all relevant services.
     *
     * @return mixed[] A list of affected service groups (either stopped, removed or
     */
    protected function reloadSupervisor(): array
    {
        $reload_result = $this->supervisor->reloadConfig();

        $affected_groups = [];

        [$reload_added, $reload_changed, $reload_removed] = $reload_result[0];

        if (!empty($reload_removed)) {
            $this->logger->debug('Removing supervisor groups.', $reload_removed);

            foreach ($reload_removed as $group) {
                $affected_groups[] = $group;

                try {
                    $this->supervisor->stopProcessGroup($group);
                    $this->supervisor->removeProcessGroup($group);
                } catch (SupervisorException) {
                }
            }
        }

        if (!empty($reload_changed)) {
            $this->logger->debug('Reloading modified supervisor groups.', $reload_changed);

            foreach ($reload_changed as $group) {
                $affected_groups[] = $group;

                try {
                    $this->supervisor->stopProcessGroup($group);
                    $this->supervisor->removeProcessGroup($group);
                    $this->supervisor->addProcessGroup($group);
                } catch (SupervisorException) {
                }
            }
        }

        if (!empty($reload_added)) {
            $this->logger->debug('Adding new supervisor groups.', $reload_added);

            foreach ($reload_added as $group) {
                $affected_groups[] = $group;

                try {
                    $this->supervisor->addProcessGroup($group);
                } catch (SupervisorException) {
                }
            }
        }

        return $affected_groups;
    }

    /**
     * Assign the first available port range to this station, or ensure it already is configured properly.
     *
     * @param Station $station
     * @param bool $force
     */
    public function assignRadioPorts(Station $station, bool $force = false): void
    {
        if (
            $station->getFrontendType() !== Adapters::FRONTEND_REMOTE
            || $station->getBackendType() !== Adapters::BACKEND_NONE
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
     *
     * @param Station|null $station A station to exclude, or null to include all stations.
     *
     * @return int The first available radio port to use.
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
     *
     * @param Station|null $except_station
     *
     * @return mixed[]
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

                if ($row['frontend_type'] !== Adapters::FRONTEND_REMOTE) {
                    $frontend_config = (array)$row['frontend_config'];

                    if (!empty($frontend_config['port'])) {
                        $port = (int)$frontend_config['port'];
                        $used_ports[$port] = $station_reference;
                    }
                }

                if ($row['backend_type'] !== Adapters::BACKEND_NONE) {
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

    protected function writeConfigurationSection(
        Station $station,
        AbstractAdapter $adapter,
        ?int $priority
    ): string {
        [, $program_name] = explode(':', $adapter->getProgramName($station));

        $config_lines = [
            'user' => 'azuracast',
            'priority' => $priority ?? 50,
            'command' => $adapter->getCommand($station),
            'directory' => $station->getRadioConfigDir(),
            'environment' => 'TZ="' . $station->getTimezone() . '"',
            'stdout_logfile' => $adapter->getLogPath($station),
            'stdout_logfile_maxbytes' => '5MB',
            'stdout_logfile_backups' => '10',
            'redirect_stderr' => 'true',
        ];

        $supervisor_config[] = '[program:' . $program_name . ']';
        foreach ($config_lines as $config_key => $config_value) {
            $supervisor_config[] = $config_key . '=' . $config_value;
        }
        $supervisor_config[] = '';

        return implode("\n", $supervisor_config);
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
            $this->supervisor->stopProcessGroup($station_group, true);
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
}
