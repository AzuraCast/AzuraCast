<?php
namespace AzuraCast\Sync;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;

abstract class SyncAbstract
{
    abstract public function run();
}