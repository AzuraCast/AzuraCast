<?php

declare(strict_types=1);

namespace App\Console\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandAbstract extends Command
{
    protected function runCommand(OutputInterface $output, string $commandName, array $commandArgs = []): int
    {
        $command = $this->getApplication()?->find($commandName);
        if (null === $command) {
            throw new RuntimeException(sprintf('Command %s not found.', $commandName));
        }

        $input = new ArrayInput(['command' => $commandName] + $commandArgs);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }
}
