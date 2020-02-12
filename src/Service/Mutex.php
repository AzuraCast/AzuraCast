<?php
namespace App\Service;

use malkusch\lock\mutex\PHPRedisMutex;
use Psr\Log\LoggerInterface;
use Redis;

class Mutex
{
    protected Redis $redis;

    protected LoggerInterface $logger;

    protected array $mutexes = [];

    public function __construct(Redis $redis, LoggerInterface $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function getMutex(string $name, int $timeout = 3): \malkusch\lock\mutex\Mutex
    {
        if (!isset($this->mutexes[$name])) {
            $mutex = new PHPRedisMutex([$this->redis], $name, $timeout);
            $mutex->setLogger($this->logger);

            $this->mutexes[$name] = $mutex;
        }

        return $this->mutexes[$name];
    }
}