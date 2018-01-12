<?php
namespace AzuraCast\Radio;

use Entity\Station;
use fXmlRpc\Exception\FaultException;
use Interop\Container\ContainerInterface;
use Supervisor\Process;

abstract class AdapterAbstract
{
    /** @var ContainerInterface */
    protected $di;

    /** @var Station */
    protected $station;

    /** @var \Supervisor\Supervisor */
    protected $supervisor;

    /**
     * @param Station $station
     */
    public function __construct(ContainerInterface $di, Station $station)
    {
        $this->di = $di;
        $this->supervisor = $di['supervisor'];

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
        if (APP_TESTING_MODE) {
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
                $this->log(_('Process stopped.'), 'green');
            } catch (FaultException $e) {
                if (stristr($e->getMessage(), 'NOT_RUNNING') !== false) {
                    $this->log(_('Process was not running!'), 'blue');
                } else {
                    $app_e = new \App\Exception($e->getMessage(), $e->getCode(), $e);

                    try {
                        $app_e->addExtraData('Supervisord Process Info', $this->supervisor->getProcessInfo($program_name));
                        $app_e->addExtraData('Supervisord Log', explode("\n", $this->supervisor->readProcessLog($program_name, 0, 0)));

                        $this->supervisor->clearProcessLogs($program_name);
                    } catch(FaultException $e) {}

                    throw $app_e;
                }
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
                $this->log(_('Process started.'), 'green');
            } catch (FaultException $e) {
                if (stristr($e->getMessage(), 'ALREADY_STARTED') !== false) {
                    $this->log(_('Process is already running!'), 'green');
                } else {
                    $app_e = new \App\Exception($e->getMessage(), $e->getCode(), $e);

                    try {
                        $app_e->addExtraData('Supervisord Process Info', $this->supervisor->getProcessInfo($program_name));
                        $app_e->addExtraData('Supervisord Log', explode("\n", $this->supervisor->readProcessLog($program_name, 0, 0)));

                        $this->supervisor->clearProcessLog($program_name);
                    } catch(FaultException $e) {}

                    throw $app_e;
                }
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
     * Log a message to console or to flash (if interactive session).
     *
     * @param $message
     */
    public function log($message, $class = 'info')
    {
        if (empty($message)) {
            return;
        }

        if (!APP_IS_COMMAND_LINE) {
            $flash = $this->di['flash'];
            $flash->addMessage($message, $class, true);
        }

        $log_file = APP_INCLUDE_TEMP . '/radio_adapter_log.txt';
        $log_message = str_pad(date('Y-m-d g:ia'), 20, ' ', STR_PAD_RIGHT) . $message . "\n";

        file_put_contents($log_file, $log_message, FILE_APPEND);

        if (!APP_TESTING_MODE) {
            \App\Debug::log('[' . strtoupper($class) . '] ' . $message);
        }
    }

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
}