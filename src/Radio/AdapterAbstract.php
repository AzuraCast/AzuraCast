<?php
namespace App\Radio;

use Doctrine\ORM\EntityManager;
use App\Entity\Station;
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
    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    /**
     * Write configuration from Station object to the external service.
     * @return bool
     */
    abstract public function write(): bool;

    /**
     * Return the shell command required to run the program.
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return null;
    }

    /**
     * Return a boolean indicating whether the adapter has an executable command associated with it.
     * @return bool
     */
    public function hasCommand(): bool
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
    public function isRunning(): bool
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
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\NotRunning
     */
    public function stop(): void
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
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\AlreadyRunning
     */
    public function start(): void
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
     *
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\AlreadyRunning
     * @throws \App\Exception\Supervisor\NotRunning
     */
    public function restart(): void
    {
        $this->stop();
        $this->start();
    }

    /**
     * Return the program's fully qualified supervisord name.
     * @return string
     */
    abstract public function getProgramName(): string;

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

    /**
     * Internal handling of any Supervisor-related exception, to add richer data to it.
     *
     * @param FaultException $e
     * @param $program_name
     * @throws \App\Exception\Supervisor
     * @throws \App\Exception\Supervisor\AlreadyRunning
     * @throws \App\Exception\Supervisor\NotRunning
     */
    protected function _handleSupervisorException(FaultException $e, $program_name): void
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

        $app_e->addLoggingContext('station_id', $this->station->getId());
        $app_e->addLoggingContext('station_name', $this->station->getName());

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
}
