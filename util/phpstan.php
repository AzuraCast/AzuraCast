<?php
/**
 * PHPStan Bootstrap File
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

require dirname(__DIR__) . '/vendor/autoload.php';

const AZURACAST_VERSION = App\Version::FALLBACK_VERSION;
const AZURACAST_API_URL = 'https://localhost/api';
const AZURACAST_API_NAME = 'Testing API';

$tempDir = sys_get_temp_dir();

App\AppFactory::createCli(
    [
        App\Environment::TEMP_DIR => $tempDir,
        App\Environment::UPLOADS_DIR => $tempDir,
    ]
);
