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
        if (false !== stripos($e->getMessage(), 'ALREADY_STARTED')) {
            $app_e = new \App\Exception\Supervisor\AlreadyRunning(
                sprintf('Adapter "%s" cannot start; was already running.', static::class),
                $e->getCode(),
                $e
            );
        } else if (false !== stripos($e->getMessage(), 'NOT_RUNNING')) {
            $app_e = new \App\Exception\Supervisor\NotRunning(
                sprintf('Adapter "%s" cannot start; was already running.', static::class),
                $e->getCode(),
                $e
            );
        } else {
            $app_e = new \App\Exception\Supervisor($e->getMessage(), $e->getCode(), $e);
        }

        $app_e->addLoggingContext('station_id', $station->getId());
        $app_e->addLoggingContext('station_name', $station->getName());

        // Get more detailed information for more significant errors.
        if ($app_e->getLoggerLevel() !== Logger::INFO) {
            $process_log = $this->supervisor->tailProcessLog($program_name, 0, 0);
            $process_log = array_filter(explode("\n", $process_log[0]));

            $app_e->addExtraData('Supervisord Log', $process_log);
            $this->supervisor->clearProcessLogs($program_name);

            $app_e->addExtraData('Supervisord Process Info', $this->supervisor->getProcessInfo($program_name));
        }

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
