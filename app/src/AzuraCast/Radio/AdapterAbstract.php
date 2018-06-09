<?php
namespace AzuraCast\Radio;

use Doctrine\ORM\EntityManager;
use Entity\Station;
use fXmlRpc\Exception\FaultException;
use Monolog\Logger;
use Supervisor\Process;
use Supervisor\Supervisor;

abstract class AdapterAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Supervisor */
    protected $supervisor;

    /** @var Logger */
    protected $logger;

    /** @var Station */
    protected $station;

    /**
     * AdapterAbstract constructor.
     * @param EntityManager $em
     * @param Supervisor $supervisor
     * @param Logger $logger
     */
    public function __construct(EntityManager $em, Supervisor $supervisor, Logger $logger)
    {
        $this->em = $em;
        $this->supervisor = $supervisor;
        $this->logger = $logger;
    }

    /**
     * @param Station $station
     */
    public function setStation(Station $station)
    {
        $this->station = $station;
    }

    /**
     * Read configuration from external service to Station object.
     * @return bool
     */
    abstract public function read();

    /**
     * Write configuration from Station object to the external service.
     * @return bool
     */
    abstract public function write();

    /**
     * Return the shell command required to run the program.
     * @return string|null
     */
    public function getCommand()
    {
        return null;
    }

    /**
     * Return a boolean indicating whether the adapter has an executable command associated with it.
     * @return bool
     */
    public function hasCommand()
    {
        if (APP_TESTING_MODE || !$this->station->isEnabled()) {
            return false;
        }

        return ($this->getCommand() !== null);
    }

    /**
     * Check if the service is running.
     *
     * @return bool
     */
    public function isRunning()
    {
        if ($this->hasCommand()) {
            $program_name = $this->getProgramName();
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
     * @throws \App\Exception
     */
    public function stop()
    {
        if ($this->hasCommand()) {
            $program_name = $this->getProgramName();

            try {
                $this->supervisor->stopProcess($program_name);
                $this->logger->info('Adapter "'.get_called_class().'" stopped.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name);
            }
        }
    }

    /**
     * Start the executable service.
     *
     * @throws \App\Exception
     */
    public function start()
    {
        if ($this->hasCommand()) {
            $program_name = $this->getProgramName();

            try {
                $this->supervisor->startProcess($program_name);
                $this->logger->info('Adapter "'.get_called_class().'" started.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name);
            }
        }
    }

    /**
     * Restart the executable service.
     */
    public function restart()
    {
        $this->stop();
        $this->start();
    }

    /**
     * Return the program's fully qualified supervisord name.
     * @return bool
     */
    abstract public function getProgramName();

    /**
     * Indicate if the adapter in question is installed on the server.
     *
     * @return bool
     */
    public static function isInstalled()
    {
        return (static::getBinary() !== false);
    }

    /**
     * Return the binary executable location for this item.
     *
     * @return bool
     */
    public static function getBinary()
    {
        return true;
    }

    /**
     * Internal handling of any Supervisor-related exception, to add richer data to it.
     *
     * @param FaultException $e
     * @param $program_name
     * @throws \AzuraCast\Exception\Supervisor
     * @throws \AzuraCast\Exception\Supervisor\AlreadyRunning
     * @throws \AzuraCast\Exception\Supervisor\NotRunning
     */
    protected function _handleSupervisorException(FaultException $e, $program_name)
    {
        if (false !== stripos($e->getMessage(), 'ALREADY_STARTED')) {
            $app_e = new \AzuraCast\Exception\Supervisor\AlreadyRunning(
                sprintf('Adapter "%s" cannot start; was already running.', get_called_class()),
                $e->getCode(),
                $e
            );
        } else if (false !== stripos($e->getMessage(), 'NOT_RUNNING')) {
            $app_e = new \AzuraCast\Exception\Supervisor\NotRunning(
                sprintf('Adapter "%s" cannot start; was already running.', get_called_class()),
                $e->getCode(),
                $e
            );
        } else {
            $app_e = new \AzuraCast\Exception\Supervisor($e->getMessage(), $e->getCode(), $e);
        }

        $app_e->addLoggingContext('station_id', $this->station->getId());
        $app_e->addLoggingContext('station_name', $this->station->getName());

        // Get more detailed information for more significant errors.
        if ($app_e->getLoggerLevel() !== Logger::INFO) {
            try {
                $process_log = $this->supervisor->tailProcessLog($program_name, 0, 0);
                $process_log = array_filter(explode("\n", $process_log[0]));

                $app_e->addExtraData('Supervisord Log', $process_log);
                $this->supervisor->clearProcessLogs($program_name);

                $app_e->addExtraData('Supervisord Process Info', $this->supervisor->getProcessInfo($program_name));
            } catch(FaultException $e) {
                throw $e;
            }
        }

        throw $app_e;
    }
}