<?php
namespace App\Console\Command;

use App\Console\Application;
use Symfony\Component\Console\Command\Command;

abstract class CommandAbstract extends Command
{
    /**
     * Return a Dependency Injection service.
     *
     * @param $service_name
     * @return mixed
     * @throws \App\Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function get($service_name)
    {
        /** @var Application $application */
        $application = self::getApplication();

        return $application->getService($service_name);
    }

    /**
     * @param OutputInterface $output
     * @param $command_name
     * @param array $command_args
     * @throws \Exception
     */
    protected function runCommand(OutputInterface $output, $command_name, $command_args = [])
    {
        $command = $this->getApplication()->find($command_name);

        $input = new ArrayInput(['command' => $command_name] + $command_args);
        $input->setInteractive(false);

        $command->run($input, $output);
    }
}
