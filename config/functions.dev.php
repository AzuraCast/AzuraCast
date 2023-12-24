<?php

declare(strict_types=1);

/**
 * @throws App\Exception\DumpException
 */
function dumpdie(mixed ...$vars): never
{
    $env = App\Environment::getInstance();

    if ($env->isCli()) {
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
