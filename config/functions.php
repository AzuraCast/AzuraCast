<?php

declare(strict_types=1);

function env(): App\Environment
{
    return App\Environment::getInstance();
}

function logger(): Monolog\Logger
{
    return App\Utilities\Logger::getInstance();
}

/**
 * @throws App\Exception\DumpException
 */
function dumpdie(mixed ...$vars): never
{
    if (env()->isCli()) {
        $dumper = new Symfony\Component\VarDumper\Dumper\CliDumper();
        $varCloner = new Symfony\Component\VarDumper\Cloner\VarCloner();

        foreach ($vars as $var) {
            $dumper->dump($varCloner->cloneVar($var), true);
        }
        exit(1);
    }

    $dumper = new Symfony\Component\VarDumper\Dumper\HtmlDumper();
    $varCloner = new Symfony\Component\VarDumper\Cloner\VarCloner();

    $dumps = [];
    foreach ($vars as $var) {
        $dumps[] = $dumper->dump($varCloner->cloneVar($var), true);
    }

    throw new App\Exception\DumpException(dumps: $dumps);
}
