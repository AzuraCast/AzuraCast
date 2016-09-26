<?php
namespace App\Radio\Backend;

use Entity\Station;

abstract class AdapterAbstract
{
    protected $station;

    /**
     * @param Station $station
     */
    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    /**
     * Read configuration from external service to Station object.
     * @return bool
     */
    public function read()
    {
        return false;
    }

    /**
     * Write configuration from Station object to the external service.
     * @return bool
     */
    public function write()
    {
        return false;
    }

    /**
     * Restart the executable service.
     * @return mixed
     */
    public function restart()
    {
        return null;
    }

    /**
     * Log a message to console or to flash (if interactive session).
     *
     * @param $message
     */
    public function log($message)
    {
        if (empty($message))
            return false;

        if (!APP_IS_COMMAND_LINE)
        {
            $di = $GLOBALS['di'];
            $flash = $di->get('flash');

            $flash->addMessage('<b>Radio Backend:</b><br>'.$message, 'info', true);
        }
        else
        {
            $log_file = APP_INCLUDE_TEMP.'/radio_backend_log.txt';
            $log_message = "\n".$message;

            file_put_contents($log_file, $log_message, FILE_APPEND);

            \App\Debug::log('Radio Backend: '.$message);
        }
    }
}