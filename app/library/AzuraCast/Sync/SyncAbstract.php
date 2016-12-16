<?php
namespace AzuraCast\Sync;

use Interop\Container\ContainerInterface;

abstract class SyncAbstract
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
    }

    abstract public function run();
}