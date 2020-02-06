<?php
namespace App\Console\Command;

use App\Console\Application;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandAbstract
{
    /** @var Application */
    protected $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * @param OutputInterface $output
     * @param string $command_name
     * @param array $command_args
     *
     * @throws Exception
     */
    protected function runCommand(OutputInterface $output, $command_name, $command_args = [])
    {
        $command = $this->getApplication()->find($command_name);

        $input = new ArrayInput(['command' => $command_name] + $command_args);
        $input->setInteractive(false);

        $command->run($input, $output);
    }
}
