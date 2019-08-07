<?php
// This is global bootstrap for autoloading
$autoloader = require __DIR__.'/../vendor/autoload.php';
$autoloader->addClassMap([
    'CestAbstract' => __DIR__.'/functional/CestAbstract.php',
]);

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$autoloader, 'loadClass']);

$GLOBALS['autoloader'] = $autoloader;

if (!function_exists('__')) {
    $translator = new \Gettext\Translator();
    $translator->register();
}
