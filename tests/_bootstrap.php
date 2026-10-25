<?php
// This is global bootstrap for autoloading
$autoloader = require __DIR__.'/../vendor/autoload.php';
$autoloader->addClassMap([
    'Functional\CestAbstract' => __DIR__ . '/Functional/CestAbstract.php',
]);

// Strip the "final" keyword from repository classes at test runtime so the
// in-memory test harness can subclass them with lightweight fakes
DG\BypassFinals::allowPaths([
    '*/backend/src/Entity/Repository/*',
]);
DG\BypassFinals::enable(bypassReadOnly: false, bypassFinal: true);

if (!function_exists('__')) {
    Gettext\TranslatorFunctions::register(new Gettext\Translator());
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
