<?php
namespace App\Console\Command;

use Interop\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;

abstract class CommandAbstract extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    public function __construct(ContainerInterface $di, $name = null)
    {
        $this->di = $di;
        parent::__construct($name);
    }
}