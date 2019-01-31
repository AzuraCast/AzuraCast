<?php
/**
 * PHPStan Bootstrap File
 */

// Register gettext to avoid related errors
$translator = new \Gettext\Translator();
$translator->register();

// Define APP_ constants used by AzuraCast.
define('APP_IS_COMMAND_LINE', true);
define('APP_INCLUDE_ROOT', dirname(__DIR__));
define('APP_INCLUDE_TEMP', dirname(APP_INCLUDE_ROOT).'/www_tmp');

define('APP_INSIDE_DOCKER', true);
define('APP_DOCKER_REVISION', 1);

define('APP_TESTING_MODE', true);
define('SAMPLE_TIMESTAMP', rand(time() - 86400, time() + 86400));

define('APP_APPLICATION_ENV', 'testing');
define('APP_IN_PRODUCTION', false);
