<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandAbstract
{
    protected Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    protected function runCommand(OutputInterface $output, string $command_name, array $command_args = []): void
    {
        $command = $this->getApplication()->find($command_name);

        $input = new ArrayInput(['command' => $command_name] + $command_args);
        $input->setInteractive(false);

        $command->run($input, $output);
    }
}
