<?php
namespace App\Radio;

use Entity\Station;
use Interop\Container\ContainerInterface;

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
        return NULL;
    }

    /**
     * Return a boolean indicating whether the adapter has an executable command associated with it.
     * @return bool
     */
    public function hasCommand()
    {
        if (APP_TESTING_MODE)
            return false;

        return ($this->getCommand() !== null);
    }

    public function isRunning()
    {
        if ($this->hasCommand())
        {
            $program_name = $this->getProgramName();
            $process = $this->supervisor->getProcess($program_name);
            return $process->isRunning();
        }
    }

    /**
     * Stop the executable service.
     * @return mixed
     */
    public function stop()
    {
        if ($this->hasCommand())
        {
            $program_name = $this->getProgramName();
            $this->supervisor->stopProcess($program_name);
        }
    }

    /**
     * Start the executable service.
     * @return mixed
     */
    public function start()
    {
        if ($this->hasCommand())
        {
            $program_name = $this->getProgramName();
            $this->supervisor->startProcess($program_name);
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
        if (empty($message))
            return;

        if (!APP_IS_COMMAND_LINE)
        {
            $flash = $this->di['flash'];
            $flash->addMessage($message, $class, true);
        }

        $log_file = APP_INCLUDE_TEMP.'/radio_adapter_log.txt';
        $log_message = str_pad(date('Y-m-d g:ia'), 20, ' ', STR_PAD_RIGHT).$message."\n";

        file_put_contents($log_file, $log_message, FILE_APPEND);

        if (!APP_TESTING_MODE)
            \App\Debug::log('['.strtoupper($class).'] '.$message);
    }
}