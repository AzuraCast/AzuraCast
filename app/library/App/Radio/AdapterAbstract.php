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
     * Attempt to kill the process represented by the specified pidfile.
     *
     * @param $pid_file
     */
    protected function _killPid($pid_file)
    {
        if (file_exists($pid_file))
        {
            $pid = file_get_contents($pid_file);

            $cmd = \App\Utilities::run_command('kill -9 '.$pid);

            if (!empty($cmd['output']))
                $this->log($cmd['output']);

            if (!empty($cmd['error']))
                $this->log($cmd['error'], 'red');

            @unlink($pid_file);
        }
        else
        {
            $this->log('No PID file found.');
        }
    }

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
     * Check the running status of the process identified by the specified pidfile.
     *
     * @param $pid_file
     * @return bool
     */
    protected function _isPidRunning($pid_file)
    {
        if (file_exists($pid_file))
        {
            $pid = file_get_contents($pid_file);

            $cmd = \App\Utilities::run_command('ps --pid '.$pid);

            /*
             * if (!empty($cmd['output']))
                $this->log($cmd['output']);
            */

            if (!empty($cmd['error']))
                $this->log($cmd['error'], 'red');

            return !empty($cmd['output']);
        }

        return false;
    }

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