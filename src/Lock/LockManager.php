<?php
namespace App\Lock;

use Psr\SimpleCache\CacheInterface;

class LockManager
{
    protected CacheInterface $cache;

    protected array $locks = [];

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getLock(string $identifier, int $timeout = 30): Lock
    {
        if (!isset($this->locks[$identifier])) {
            $this->locks[$identifier] = new Lock($this->cache, $identifier, $timeout);
        }

        return $this->locks[$identifier];
    }
}