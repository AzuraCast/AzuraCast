<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

require dirname(__FILE__).'/../app/bootstrap.php';

define('VERSION', '1.0.0');

$loader = new \Phalcon\Loader();
$loader->registerDirs(array(
    APP_INCLUDE_MODULES.'/cli/tasks',
));
$loader->register();

// Create a console application
$console = new \Phalcon\CLI\Console;
$console->setDI($di);

// Process the console arguments
$arguments = array();
foreach($argv as $k => $arg) {
    if ($k == 1) {
        $task_parts = explode(':', $arg);
        $arguments['task'] = $task_parts[0];
        $arguments['action'] = (isset($task_parts[1])) ? $task_parts[1] : 'index';
    } elseif ($k > 1) {
        $arguments['params'][] = $arg;
    }
}

// define global constants for the current task and action
define('CURRENT_TASK', $arguments['task']);
define('CURRENT_ACTION', $arguments['action']);

try
{
    // handle incoming arguments
    $console->handle($arguments);
}
catch (\Phalcon\Exception $e) {
    echo $e->getMessage();
    exit(255);
}