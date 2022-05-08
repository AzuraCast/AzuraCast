<?php
// This is global bootstrap for autoloading
$autoloader = require __DIR__.'/../vendor/autoload.php';
$autoloader->addClassMap([
    'Functional\CestAbstract' => __DIR__ . '/Functional/CestAbstract.php',
]);

if (!function_exists('__')) {
    PhpMyAdmin\MoTranslator\Loader::loadFunctions();
}

// Clear output directory
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = array_diff(scandir($dir, SCANDIR_SORT_NONE) ?: [], ['.', '..', '.gitignore']);
        foreach ($objects as $object) {
            if (is_dir($dir . '/' . $object)) {
                rrmdir($dir . '/' . $object);
            } else {
                unlink($dir . '/' . $object);
            }
        }
        reset($objects);
        @rmdir($dir);
    }
}

rrmdir(__DIR__ . '/_output');
