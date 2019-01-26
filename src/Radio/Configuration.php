<?php
namespace App\Radio;

use Doctrine\ORM\EntityManager;
use App\Entity\Station;
use Supervisor\Supervisor;

class Configuration
{
    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /** @var Supervisor */
    protected $supervisor;

    /**
     * Configuration constructor.
     * @param EntityManager $em
     * @param Adapters $adapters
     * @param Supervisor $supervisor
     */
    public function __construct(EntityManager $em, Adapters $adapters, Supervisor $supervisor)
    {
        $this->em = $em;
        $this->adapters = $adapters;
        $this->supervisor = $supervisor;

        $this->used_ports = [];
    }

    /**
     * Write all configuration changes to the filesystem and reload supervisord.
     *
     * @param Station $station
     * @param bool $regen_auth_key
     * @param bool $force_restart Always restart this station's supervisor instances, even if nothing changed.
     *
     * @throws \App\Exception\NotFound
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function writeConfiguration(Station $station, $regen_auth_key = false, $force_restart = false): void
    {
        if (APP_TESTING_MODE) {
            return;
        }

        // Initialize adapters.
        $config_path = $station->getRadioConfigDir();
        $supervisor_config = [];
        $supervisor_config_path = $config_path . '/supervisord.conf';

        if (!$station->isEnabled()) {
            @unlink($supervisor_config_path);
            $this->_reloadSupervisorForStation($station, false);
            return;
        }

        // Ensure port configuration exists
        $this->assignRadioPorts($station, false);

        if ($regen_auth_key || empty($station->getAdapterApiKey())) {
            $station->generateAdapterApiKey();
            $this->em->persist($station);
            $this->em->flush($station);
        }

        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        // If no processes need to be managed, remove any existing config.
        if (!$frontend->hasCommand($station) && !$backend->hasCommand($station)) {
            @unlink($supervisor_config_path);
            $this->_reloadSupervisorForStation($station, false);
            return;
        }

        // Write config files for both backend and frontend.
        $frontend->write($station);
        $backend->write($station);

        // Get group information
        $backend_name = $backend->getProgramName($station);
        [$backend_group, $backend_program] = explode(':', $backend_name);

        $frontend_name = $frontend->getProgramName($station);
        [,$frontend_program] = explode(':', $frontend_name);

        $frontend_watch_name = $frontend->getWatchProgramName($station);
        [,$frontend_watch_program] = explode(':', $frontend_watch_name);

        // Write group section of config
        $programs = [];
        if ($backend->hasCommand($station)) {
            $programs[] = $backend_program;
        }
        if ($frontend->hasCommand($station)) {
            $programs[] = $frontend_program;
        }
        if ($frontend->hasWatchCommand($station)) {
            $programs[] = $frontend_watch_program;
        }

        $supervisor_config[] = '[group:' . $backend_group . ']';
        $supervisor_config[] = 'programs=' . implode(',', $programs);
        $supervisor_config[] = '';

        // Write frontend
        if ($frontend->hasCommand($station)) {
            $this->_writeConfigurationSection($supervisor_config, $frontend_program, [
                'directory' => $config_path,
                'command' => $frontend->getCommand($station),
                'priority' => 90,
            ]);
        }

        // Write frontend watcher program
        if ($frontend->hasWatchCommand($station)) {
            $this->_writeConfigurationSection($supervisor_config, $frontend_watch_program, [
                'directory' => '/var/azuracast/servers/station-watcher',
                'command' => $frontend->getWatchCommand($station),
                'priority' => 95,
            ]);
        }

        // Write backend
        if ($backend->hasCommand($station)) {
            $this->_writeConfigurationSection($supervisor_config, $backend_program, [
                'directory' => $config_path,
                'command' => $backend->getCommand($station),
                'priority' => 100,
            ]);
        }

        // Write config contents
        $supervisor_config_data = implode("\n", $supervisor_config);
        file_put_contents($supervisor_config_path, $supervisor_config_data);

        $this->_reloadSupervisorForStation($station, $force_restart);
    }

    protected function _writeConfigurationSection(&$supervisor_config, $program_name, $config_lines): void
    {
        $defaults = [
            'user' => 'azuracast',
            'priority' => 100,
        ];

        if (APP_INSIDE_DOCKER) {
            $defaults['stdout_logfile'] = '/dev/stdout';
            $defaults['stdout_logfile_maxbytes'] = 0;
            $defaults['stderr_logfile'] = '/dev/stderr';
            $defaults['stderr_logfile_maxbytes'] = 0;
        }

        $supervisor_config[] = '[program:' . $program_name . ']';
        $config_lines = array_merge($defaults, $config_lines);

        foreach($config_lines as $config_key => $config_value) {
            $supervisor_config[] = $config_key . '=' . $config_value;
        }

        $supervisor_config[] = '';
    }

    /**
     * Remove configuration (i.e. prior to station removal) and trigger a Supervisor refresh.
     *
     * @param Station $station
     */
    public function removeConfiguration(Station $station): void
    {
        if (APP_TESTING_MODE) {
            return;
        }

        $config_path = $station->getRadioConfigDir();
        $supervisor_config_path = $config_path . '/supervisord.conf';

        @unlink($supervisor_config_path);

        $this->_reloadSupervisor();
    }

    /**
     * Trigger a supervisord reload/restart for a station, optionally forcing a restart of the station's
     * service group.
     *
     * @param Station $station
     * @param bool $force_restart
     */
    protected function _reloadSupervisorForStation(Station $station, $force_restart = false): void
    {
        $station_group = 'station_'.$station->getId();
        $affected_groups = $this->_reloadSupervisor();

        $was_restarted = in_array($station_group, $affected_groups, true);

        if (!$was_restarted && $force_restart) {
            $this->supervisor->stopProcessGroup($station_group, true);
            $this->supervisor->startProcessGroup($station_group, true);
            $was_restarted = true;
        }

        if ($was_restarted) {
            $station->setHasStarted(true);
            $station->setNeedsRestart(false);

            $this->em->persist($station);
            $this->em->flush($station);
        }
    }

    /**
     * Trigger a supervisord reload and restart all relevant services.
     *
     * @return array A list of affected service groups (either stopped, removed or
     */
    protected function _reloadSupervisor(): array
    {
        $reload_result = $this->supervisor->reloadConfig();

        $affected_groups = [];

        [$reload_added, $reload_changed, $reload_removed] = $reload_result[0];

        foreach ($reload_removed as $group) {
            $affected_groups[] = $group;
            $this->supervisor->stopProcessGroup($group);
            $this->supervisor->removeProcessGroup($group);
        }

        foreach ($reload_changed as $group) {
            $affected_groups[] = $group;
            $this->supervisor->stopProcessGroup($group);
            $this->supervisor->removeProcessGroup($group);
            $this->supervisor->addProcessGroup($group);
        }

        foreach ($reload_added as $group) {
            $affected_groups[] = $group;
            $this->supervisor->addProcessGroup($group);
        }

        return $affected_groups;
    }

    /**
     * Assign the first available port range to this station, or ensure it already is configured properly.
     *
     * @param Station $station
     * @param bool $force
     */
    public function assignRadioPorts(Station $station, $force = false): void
    {
        if ($station->getFrontendType() !== Adapters::FRONTEND_REMOTE || $station->getBackendType() !== Adapters::BACKEND_NONE) {
            $frontend_config = (array)$station->getFrontendConfig();
            $backend_config = (array)$station->getBackendConfig();

            if (empty($frontend_config['port']) || $force) {
                $base_port = $this->getFirstAvailableRadioPort($station);

                $station->setFrontendConfig([
                    'port' => $base_port,
                ]);
            } else {
                $base_port = (int)$frontend_config['port'];
            }

            if (empty($backend_config['dj_port'])) {
                $station->setBackendConfig([
                    'dj_port' => $base_port + 5,
                ]);
            }

            if (empty($backend_config['telnet_port'])) {
                $station->setBackendConfig([
                    'telnet_port' => $base_port + 4,
                ]);
            }

            $this->em->persist($station);
            $this->em->flush();
        }
    }

    /**
     * Determine the first available 10-port block that has no stations occupying it.
     *
     * @param Station|null $station A station to exclude, or null to include all stations.
     * @return int The first available radio port to use.
     */
    public function getFirstAvailableRadioPort(Station $station = null): int
    {
        $used_ports = $this->getUsedPorts($station);

        // Iterate from port 8000 to 9000, in increments of 10
        $protected_ports = [8080];

        for($port = 8000; $port < 9000; $port += 10) {
            if (in_array($port, $protected_ports)) {
                continue;
            }

            $range_in_use = false;
            for($i = $port; $i < $port+10; $i++) {
                if (isset($used_ports[$i])) {
                    $range_in_use = true;
                    break;
                }
            }

            if (!$range_in_use) {
                return $port;
            }
        }

        throw new \Azura\Exception('This installation has no available ports for new radio stations.');
    }

    /**
     * Get an array of all used ports across the system, except the ones used by the station specified (if specified).
     *
     * @param Station|null $except_station
     * @return array
     */
    public function getUsedPorts(Station $except_station = null): array
    {
        static $used_ports;

        if (null === $used_ports) {
            $used_ports = [];

            // Get all station used ports.
            $station_configs = $this->em->createQuery('SELECT s.id, s.name, s.frontend_type, s.frontend_config, s.backend_type, s.backend_config FROM '.Station::class.' s')
                ->getArrayResult();

            foreach($station_configs as $row) {
                $station_reference = ['id' => $row['id'], 'name' => $row['name']];

                if ($row['frontend_type'] !== Adapters::FRONTEND_REMOTE && $row['backend_type'] !== Adapters::BACKEND_NONE) {
                    $frontend_config = (array)$row['frontend_config'];

                    if (!empty($frontend_config['port'])) {
                        $port = (int)$frontend_config['port'];
                        $used_ports[$port] = $station_reference;
                    }

                    $backend_config = (array)$row['backend_config'];

                    if (!empty($backend_config['dj_port'])) {
                        $port = (int)$frontend_config['dj_port'];
                        $used_ports[$port] = $station_reference;
                    }
                    if (!empty($backend_config['telnet_port'])) {
                        $port = (int)$frontend_config['telnet_port'];
                        $used_ports[$port] = $station_reference;
                    }
                }
            }
        }

        if ($except_station !== null) {
            return array_filter($used_ports, function($station_reference) use ($except_station) {
                return ($station_reference['id'] !== $except_station->getId());
            });
        }

        return $used_ports;
    }
}
