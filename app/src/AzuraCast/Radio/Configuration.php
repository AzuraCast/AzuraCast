<?php
namespace AzuraCast\Radio;

use Doctrine\ORM\EntityManager;
use Entity\Station;
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
    }

    /**
     * Write all configuration changes to the filesystem and reload supervisord.
     *
     * @param Station $station
     * @param bool $regen_auth_key Regenerate the API authorization key (will trigger a full reset of processes).
     * @throws \Exception
     */
    public function writeConfiguration(Station $station, $regen_auth_key = false)
    {
        if (APP_TESTING_MODE) {
            return;
        }

        if ($regen_auth_key || empty($station->getAdapterApiKey())) {
            $station->generateAdapterApiKey();
            $this->em->persist($this);
            $this->em->flush();
        }

        // Initialize adapters.
        $config_path = $station->getRadioConfigDir();
        $supervisor_config = [];
        $supervisor_config_path = $config_path . '/supervisord.conf';

        $frontend = $this->adapters->getFrontendAdapter($station);
        $backend = $this->adapters->getBackendAdapter($station);

        // If no processes need to be managed, remove any existing config.
        if (!$frontend->hasCommand() && !$backend->hasCommand()) {
            @unlink($supervisor_config_path);
            $this->_reloadSupervisor();
            return;
        }

        // Write config files for both backend and frontend.
        $frontend->write();
        $backend->write();

        // Get group information
        $backend_name = $backend->getProgramName();
        list($backend_group, $backend_program) = explode(':', $backend_name);

        $frontend_name = $frontend->getProgramName();
        list(,$frontend_program) = explode(':', $frontend_name);

        $frontend_watch_name = $frontend->getWatchProgramName();
        list(,$frontend_watch_program) = explode(':', $frontend_watch_name);

        // Write group section of config
        $programs = [];
        if ($backend->hasCommand()) {
            $programs[] = $backend_program;
        }
        if ($frontend->hasCommand()) {
            $programs[] = $frontend_program;
        }
        if ($frontend->hasWatchCommand()) {
            $programs[] = $frontend_watch_program;
        }

        $supervisor_config[] = '[group:' . $backend_group . ']';
        $supervisor_config[] = 'programs=' . implode(',', $programs);
        $supervisor_config[] = '';

        // Write frontend
        if ($frontend->hasCommand()) {
            $supervisor_config[] = '[program:' . $frontend_program . ']';
            $supervisor_config[] = 'directory=' . $config_path;
            $supervisor_config[] = 'command=' . $frontend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=90';

            if (APP_INSIDE_DOCKER) {
                $supervisor_config[] = 'stdout_logfile=/dev/stdout';
                $supervisor_config[] = 'stdout_logfile_maxbytes=0';
                $supervisor_config[] = 'stderr_logfile=/dev/stderr';
                $supervisor_config[] = 'stderr_logfile_maxbytes=0';
            }

            $supervisor_config[] = '';
        }

        // Write frontend watcher program
        if ($frontend->hasWatchCommand()) {
            $supervisor_config[] = '[program:' . $frontend_watch_program . ']';
            $supervisor_config[] = 'directory=/var/azuracast/servers/station-watcher';
            $supervisor_config[] = 'command=' . $frontend->getWatchCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=95';

            if (APP_INSIDE_DOCKER) {
                $supervisor_config[] = 'stdout_logfile=/dev/stdout';
                $supervisor_config[] = 'stdout_logfile_maxbytes=0';
                $supervisor_config[] = 'stderr_logfile=/dev/stderr';
                $supervisor_config[] = 'stderr_logfile_maxbytes=0';
            }

            $supervisor_config[] = '';
        }

        // Write backend
        if ($backend->hasCommand()) {
            $supervisor_config[] = '[program:' . $backend_program . ']';
            $supervisor_config[] = 'directory=' . $config_path;
            $supervisor_config[] = 'command=' . $backend->getCommand();
            $supervisor_config[] = 'user=azuracast';
            $supervisor_config[] = 'priority=100';

            if (APP_INSIDE_DOCKER) {
                $supervisor_config[] = 'stdout_logfile=/dev/stdout';
                $supervisor_config[] = 'stdout_logfile_maxbytes=0';
                $supervisor_config[] = 'stderr_logfile=/dev/stderr';
                $supervisor_config[] = 'stderr_logfile_maxbytes=0';
            }

            $supervisor_config[] = '';
        }

        // Write config contents
        $supervisor_config_data = implode("\n", $supervisor_config);
        file_put_contents($supervisor_config_path, $supervisor_config_data);

        $this->_reloadSupervisor();
    }

    /**
     * Remove configuration (i.e. prior to station removal) and trigger a Supervisor refresh.
     *
     * @param Station $station
     */
    public function removeConfiguration(Station $station)
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
     * Trigger a supervisord reload and restart all relevant services.
     */
    protected function _reloadSupervisor()
    {
        $reload_result = $this->supervisor->reloadConfig();

        $reload_added = $reload_result[0][0];
        $reload_changed = $reload_result[0][1];
        $reload_removed = $reload_result[0][2];

        foreach ($reload_removed as $group) {
            $this->supervisor->stopProcessGroup($group);
            $this->supervisor->removeProcessGroup($group);
        }

        foreach ($reload_changed as $group) {
            $this->supervisor->stopProcessGroup($group);
            $this->supervisor->removeProcessGroup($group);
            $this->supervisor->addProcessGroup($group);
        }

        foreach ($reload_added as $group) {
            $this->supervisor->addProcessGroup($group);
        }
    }
}