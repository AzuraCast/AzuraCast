<?php

declare(strict_types=1);

namespace App\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandAbstract extends Command
{
    protected function runCommand(OutputInterface $output, string $command_name, array $command_args = []): int
    {
        $command = $this->getApplication()?->find($command_name);
        if (null === $command) {
            throw new \RuntimeException(sprintf('Command %s not found.', $command_name));
        }

        $input = new ArrayInput(['command' => $command_name] + $command_args);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }
}
