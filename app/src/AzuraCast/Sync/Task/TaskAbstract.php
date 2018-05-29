<?php
namespace AzuraCast\Sync\Task;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;

abstract class TaskAbstract
{
    abstract public function run($force = false);
}