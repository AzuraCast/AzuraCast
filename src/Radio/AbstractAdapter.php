<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity;
use App\Environment;
use App\Exception\Supervisor\AlreadyRunningException;
use App\Exception\Supervisor\BadNameException;
use App\Exception\Supervisor\NotRunningException;
use App\Exception\SupervisorException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Supervisor\Exception\Fault;
use Supervisor\Exception\SupervisorException as SupervisorLibException;
use Supervisor\Process;
use Supervisor\Supervisor;

abstract class AbstractAdapter
{
    public function __construct(
        protected Environment $environment,
        protected EntityManagerInterface $em,
        protected Supervisor $supervisor,
        protected EventDispatcherInterface $dispatcher,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Write configuration from Station object to the external service.
     *
     * @param Entity\Station $station
     *
     * @return bool Whether the newly written configuration differs from what was already on disk.
     */
    public function write(Entity\Station $station): bool
    {
        $configPath = $this->getConfigurationPath($station);
        if (null === $configPath) {
            return false;
        }

        $currentConfig = (is_file($configPath))
            ? file_get_contents($configPath)
            : null;

        $newConfig = $this->getCurrentConfiguration($station);

        file_put_contents($configPath, $newConfig);

        return 0 !== strcmp($currentConfig ?: '', $newConfig ?: '');
    }

    /**
     * Generate the configuration for this adapter as it would exist with current database settings.
     *
     * @param Entity\Station $station
     *
     */
    public function getCurrentConfiguration(Entity\Station $station): ?string
    {
        return null;
    }

    /**
     * Returns the main path where configuration data is stored for this adapter.
     *
     */
    public function getConfigurationPath(Entity\Station $station): ?string
    {
        return null;
    }

    /**
     * Indicate if the adapter in question is installed on the server.
     */
    public function isInstalled(): bool
    {
        return (null !== $this->getBinary());
    }

    /**
     * Return the binary executable location for this item.
     *
     * @return string|null Returns either the path to the binary if it exists or null for no binary.
     */
    public function getBinary(): ?string
    {
        return null;
    }

    /**
     * Check if the service is running.
     *
     * @param Entity\Station $station
     */
    public function isRunning(Entity\Station $station): bool
    {
        if (!$this->hasCommand($station)) {
            return true;
        }

        $program_name = $this->getProgramName($station);

        try {
            $process = $this->supervisor->getProcess($program_name);

            return $process instanceof Process && $process->isRunning();
        } catch (Fault\BadNameException) {
            return false;
        }
    }

    /**
     * Return a boolean indicating whether the adapter has an executable command associated with it.
     *
     * @param Entity\Station $station
     */
    public function hasCommand(Entity\Station $station): bool
    {
        if ($this->environment->isTesting() || !$station->isEnabled()) {
            return false;
        }

        return ($this->getCommand($station) !== null);
    }

    /**
     * Return the shell command required to run the program.
     *
     * @param Entity\Station $station
     */
    public function getCommand(Entity\Station $station): ?string
    {
        return null;
    }

    /**
     * Return the program's fully qualified supervisord name.
     *
     * @param Entity\Station $station
     */
    abstract public function getProgramName(Entity\Station $station): string;

    /**
     * Restart the executable service.
     *
     * @param Entity\Station $station
     */
    public function restart(Entity\Station $station): void
    {
        $this->stop($station);
        $this->start($station);
    }

    /**
     * Stop the executable service.
     *
     * @param Entity\Station $station
     *
     * @throws SupervisorException
     * @throws NotRunningException
     */
    public function stop(Entity\Station $station): void
    {
        if ($this->hasCommand($station)) {
            $program_name = $this->getProgramName($station);

            try {
                $this->supervisor->stopProcess($program_name);
                $this->logger->info(
                    'Adapter "' . static::class . '" stopped.',
                    ['station_id' => $station->getId(), 'station_name' => $station->getName()]
                );
            } catch (SupervisorLibException $e) {
                $this->handleSupervisorException($e, $program_name, $station);
            }
        }
    }

    /**
     * Start the executable service.
     *
     * @param Entity\Station $station
     *
     * @throws SupervisorException
     * @throws AlreadyRunningException
     */
    public function start(Entity\Station $station): void
    {
        if ($this->hasCommand($station)) {
            $program_name = $this->getProgramName($station);

            try {
                $this->supervisor->startProcess($program_name);
                $this->logger->info(
                    'Adapter "' . static::class . '" started.',
                    ['station_id' => $station->getId(), 'station_name' => $station->getName()]
                );
            } catch (SupervisorLibException $e) {
                $this->handleSupervisorException($e, $program_name, $station);
            }
        }
    }

    /**
     * Internal handling of any Supervisor-related exception, to add richer data to it.
     *
     * @param SupervisorLibException $e
     * @param string $program_name
     * @param Entity\Station $station
     *
     * @throws AlreadyRunningException
     * @throws BadNameException
     * @throws NotRunningException
     * @throws SupervisorException
     */
    protected function handleSupervisorException(
        SupervisorLibException $e,
        string $program_name,
        Entity\Station $station
    ): void {
        $class_parts = explode('\\', static::class);
        $class_name = array_pop($class_parts);

        if ($e instanceof Fault\BadNameException) {
            $e_headline = __('%s is not recognized as a service.', $class_name);
            $e_body = __('It may not be registered with Supervisor yet. Restarting broadcasting may help.');

            $app_e = new BadNameException(
                $e_headline . '; ' . $e_body,
                $e->getCode(),
                $e
            );
        } elseif ($e instanceof Fault\AlreadyStartedException) {
            $e_headline = __('%s cannot start', $class_name);
            $e_body = __('It is already running.');

            $app_e = new AlreadyRunningException(
                $e_headline . '; ' . $e_body,
                $e->getCode(),
                $e
            );
        } elseif ($e instanceof Fault\NotRunningException) {
            $e_headline = __('%s cannot stop', $class_name);
            $e_body = __('It is not running.');

            $app_e = new NotRunningException(
                $e_headline . '; ' . $e_body,
                $e->getCode(),
                $e
            );
        } else {
            $e_headline = __('%s encountered an error', $class_name);

            // Get more detailed information for more significant errors.
            $process_log = $this->supervisor->tailProcessStdoutLog($program_name, 0, 500);
            $process_log = array_values(array_filter(explode("\n", $process_log[0])));
            $process_log = array_slice($process_log, -6);

            $e_body = (!empty($process_log))
                ? implode('<br>', $process_log)
                : __('Check the log for details.');

            $app_e = new SupervisorException($e_headline, $e->getCode(), $e);
            $app_e->addExtraData('supervisor_log', $process_log);
            $app_e->addExtraData('supervisor_process_info', $this->supervisor->getProcessInfo($program_name));
        }

        $app_e->setFormattedMessage('<b>' . $e_headline . '</b><br>' . $e_body);
        $app_e->addLoggingContext('station_id', $station->getId());
        $app_e->addLoggingContext('station_name', $station->getName());

        throw $app_e;
    }

    /**
     * Return the path where logs are written to.
     *
     * @param Entity\Station $station
     */
    public function getLogPath(Entity\Station $station): string
    {
        $config_dir = $station->getRadioConfigDir();

        $class_parts = explode('\\', static::class);
        $class_name = array_pop($class_parts);

        return $config_dir . '/' . strtolower($class_name) . '.log';
    }
}
