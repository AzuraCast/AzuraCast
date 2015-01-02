<?php

namespace Baseapp\Cli\Tasks;

/**
 * Cron CLI Task
 *
 * @package     base-app
 * @category    Task
 * @version     2.0
 */
class CronTask extends MainTask
{

    /**
     * Main Action
     *
     * @package     base-app
     * @version     2.0
     */
    public function mainAction()
    {
        echo "cronTask/mainAction\n";
    }

    public function testAction()
    {
        echo "cronTask/testAction\n";
        print_r($this->router->getParams());
    }

}
