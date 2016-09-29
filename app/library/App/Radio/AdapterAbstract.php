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

    /**
     * @param Station $station
     */
    public function __construct(ContainerInterface $di, Station $station)
    {
        $this->di = $di;
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
     * Restart the executable service.
     * @return mixed
     */
    abstract public function restart();

    /**
     * Stop the executable service.
     * @return mixed
     */
    abstract public function stop();

    /**
     * Stop the executable service.
     * @return mixed
     */
    abstract public function start();

    /**
     * Determine if the executable service is running.
     * @return bool
     */
    abstract public function isRunning();

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
        else
        {
            $log_file = APP_INCLUDE_TEMP.'/radio_backend_log.txt';
            $log_message = "\n".$message;

            file_put_contents($log_file, $log_message, FILE_APPEND);

            \App\Debug::log('['.strtoupper($class).'] '.$message);
        }
    }
}