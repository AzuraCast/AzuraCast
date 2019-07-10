<?php
namespace App\Radio;

use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use App\Entity;
use fXmlRpc\Exception\FaultException;
use Monolog\Logger;
use Supervisor\Process;
use Supervisor\Supervisor;

abstract class AbstractAdapter
{
    /** @var EntityManager */
    protected $em;

    /** @var Supervisor */
    protected $supervisor;

    /** @var Logger */
    protected $logger;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param EntityManager $em
     * @param Supervisor $supervisor
     * @param Logger $logger
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EntityManager $em, Supervisor $supervisor, Logger $logger, EventDispatcher $dispatcher)
    {
        $this->em = $em;
        $this->supervisor = $supervisor;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Write configuration from Station object to the external service.
     *
     * @param Entity\Station $station
     * @return bool
     */
    abstract public function write(Entity\Station $station): bool;

    /**
     * Return the shell command required to run the program.
     *
     * @param Entity\Station $station
     * @return string|null
     */
    public function getCommand(Entity\Station $station): ?string
    {
        return null;
    }

    /**
     * Return a boolean indicating whether the adapter has an executable command associated with it.
     *
     * @param Entity\Station $station
     * @return bool
     */
    public function hasCommand(Entity\Station $station): bool
    {
        if (APP_TESTING_MODE || !$station->isEnabled()) {
            return false;
        }

        return ($this->getCommand($station) !== null);
    }

    /**
     * Check if the service is running.
     *
     * @param Entity\Station $station
     * @return bool
     */
    public function isRunning(Entity\Station $station): bool
    {
        if ($this->hasCommand($station)) {
            $program_name = $this->getProgramName($station);
            $process = $this->supervisor->getProcess($program_name);

            if ($process instanceof Process) {
                return $process->isRunning();
            }
        }

        return false;
    }

    /**
     * Stop the executable service.
     *
     * @param Entity\Station $station
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\NotRunning
     */
    public function stop(Entity\Station $station): void
    {
        if ($this->hasCommand($station)) {
            $program_name = $this->getProgramName($station);

            try {
                $this->supervisor->stopProcess($program_name);
                $this->logger->info('Adapter "'.get_called_class().'" stopped.', ['station_id' => $station->getId(), 'station_name' => $station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name, $station);
            }
        }
    }

    /**
     * Start the executable service.
     *
     * @param Entity\Station $station
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\AlreadyRunning
     */
    public function start(Entity\Station $station): void
    {
        if ($this->hasCommand($station)) {
            $program_name = $this->getProgramName($station);

            try {
                $this->supervisor->startProcess($program_name);
                $this->logger->info('Adapter "'.get_called_class().'" started.', ['station_id' => $station->getId(), 'station_name' => $station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name, $station);
            }
        }
    }

    /**
     * Restart the executable service.
     *
     * @param Entity\Station $station
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\AlreadyRunning
     * @throws \App\Exception\Supervisor\NotRunning
     */
    public function restart(Entity\Station $station): void
    {
        $this->stop($station);
        $this->start($station);
    }

    /**
     * Return the program's fully qualified supervisord name.
     *
     * @param Entity\Station $station
     * @return string
     */
    abstract public function getProgramName(Entity\Station $station): string;

    /**
     * Return the path where logs are written to.
     *
     * @param Entity\Station $station
     * @return string
     */
     public function getLogPath(Entity\Station $station): string
     {
         $config_dir = $station->getRadioConfigDir();

         $class_parts = explode('\\', static::class);
         $class_name = array_pop($class_parts);

         return $config_dir.'/'.strtolower($class_name).'.log';
     }

    /**
     * Internal handling of any Supervisor-related exception, to add richer data to it.
     *
     * @param FaultException $e
     * @param string $program_name
     * @param Entity\Station $station
     *
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\AlreadyRunning
     * @throws \App\Exception\Supervisor\NotRunning
     */
    protected function _handleSupervisorException(FaultException $e, $program_name, Entity\Station $station): void
    {
        $class_parts = explode('\\', static::class);
        $class_name = array_pop($class_parts);

        if (false !== stripos($e->getMessage(), 'BAD_NAME')) {
            $e_headline = __('%s is not recognized as a service.', $class_name);
            $e_body = __('It may not be registered with Supervisor yet. Restarting broadcasting may help.');

            $app_e = new \App\Exception\Supervisor\BadName(
                $e_headline.'; '.$e_body,
                $e->getCode(),
                $e
            );
        } else if (false !== stripos($e->getMessage(), 'ALREADY_STARTED')) {
            $e_headline = __('%s cannot start', $class_name);
            $e_body = __('It is already running.');

            $app_e = new \App\Exception\Supervisor\AlreadyRunning(
                $e_headline.'; '.$e_body,
                $e->getCode(),
                $e
            );
        } else if (false !== stripos($e->getMessage(), 'NOT_RUNNING')) {
            $e_headline = __('%s cannot stop', $class_name);
            $e_body = __('It is not running.');

            $app_e = new \App\Exception\Supervisor\NotRunning(
                $e_headline.'; '.$e_body,
                $e->getCode(),
                $e
            );
        } else {
            $e_headline = __('%s encountered an error', $class_name);

            // Get more detailed information for more significant errors.
            $process_log = $this->supervisor->tailProcessStdoutLog($program_name, 0, 500);
            $process_log = array_filter(explode("\n", $process_log[0]));
            $process_log = array_slice($process_log, -6);

            $e_body = (!empty($process_log))
                ? implode('<br>', $process_log)
                : __('Check the log for details.');

            $app_e = new \App\Exception\Supervisor($e_headline, $e->getCode(), $e);
            $app_e->addExtraData('supervisor_log', $process_log);
            $app_e->addExtraData('supervisor_process_info', $this->supervisor->getProcessInfo($program_name));
        }

        $app_e->setFormattedMessage('<b>'.$e_headline.'</b><br>'.$e_body);
        $app_e->addLoggingContext('station_id', $station->getId());
        $app_e->addLoggingContext('station_name', $station->getName());

        throw $app_e;
    }

    /**
     * Indicate if the adapter in question is installed on the server.
     *
     * @return bool
     */
    public static function isInstalled(): bool
    {
        return (static::getBinary() !== false);
    }

    /**
     * Return the binary executable location for this item.
     */
    public static function getBinary()
    {
        return true;
    }
}
