<?php
echo 'wee';
exit;

use Phalcon\Mvc\Application;

error_reporting(E_ALL);

try {

    /**
     * Include services
     */
    require __DIR__ . '/../config/services.php';

    /**
     * Handle the request
     */
    $application = new Application($di);

    /**
     * Include modules
     */
    require __DIR__ . '/../config/modules.php';

    echo $application->handle()->getContent();

} catch (Exception $e) {
    echo 'Just Phalcon Things';
    echo $e->getMessage();
}
