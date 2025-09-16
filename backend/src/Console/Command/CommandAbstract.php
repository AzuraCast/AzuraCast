<?php

declare(strict_types=1);

namespace App\Console\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandAbstract extends Command
{
    protected function runCommand(
        OutputInterface $output,
        string $commandName,
        array|null $commandArgs = null
    ): int {
        $input = (null === $commandArgs)
            ? new StringInput($commandName)
            : new ArrayInput(['command' => $commandName] + $commandArgs);

        return $this->runCommandRaw($input, $output);
    }

    protected function runCommandRaw(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $commandName = $input->getFirstArgument();
        if (null === $commandName) {
            throw new RuntimeException('No command specified.');
        }

        $command = $this->getApplication()?->find($commandName);
        if (null === $command) {
            throw new RuntimeException(sprintf('Command %s not found.', $commandName));
        }

        $input->setInteractive(false);
        return $command->run($input, $output);
    }
}
