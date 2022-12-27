<?php

declare(strict_types=1);

namespace App\Console\Command\Traits;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

trait PassThruProcess
{
    protected function passThruProcess(
        SymfonyStyle $io,
        string|array $cmd,
        ?string $cwd = null,
        array $env = [],
        int $timeout = 14400
    ): Process {
        set_time_limit($timeout);

        if (is_array($cmd)) {
            $process = new Process($cmd, $cwd);
        } else {
            $process = Process::fromShellCommandline($cmd, $cwd);
        }

        $process->setTimeout($timeout - 60);
        $process->setIdleTimeout(null);

        $stdout = [];
        $stderr = [];

        $process->mustRun(function ($type, $data) use ($process, $io, &$stdout, &$stderr): void {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, $env);

        return $process;
    }
}
