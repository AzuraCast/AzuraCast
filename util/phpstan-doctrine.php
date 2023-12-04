<?php
/**
 * PHPStan Bootstrap File
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

require dirname(__DIR__) . '/vendor/autoload.php';

$tempDir = sys_get_temp_dir();

$ci = App\AppFactory::buildContainer([
    App\Environment::TEMP_DIR => $tempDir,
    App\Environment::UPLOADS_DIR => $tempDir,
]);

return $ci->get(Doctrine\ORM\EntityManagerInterface::class);
