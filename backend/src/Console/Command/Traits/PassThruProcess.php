<?php

declare(strict_types=1);

namespace App\Console\Command\Traits;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

trait PassThruProcess
{
    protected function passThruProcess(
        OutputInterface $output,
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

        $stderr = match (true) {
            $output instanceof SymfonyStyle => $output->getErrorStyle(),
            $output instanceof ConsoleOutputInterface => $output->getErrorOutput(),
            default => $output
        };

        $process->mustRun(function ($type, $data) use ($process, $output, $stderr): void {
            if ($process::ERR === $type) {
                $stderr->write($data);
            } else {
                $output->write($data);
            }
        }, $env);

        return $process;
    }
}
