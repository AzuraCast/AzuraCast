<?php

declare(strict_types=1);

namespace App\Service;

use DeviceDetector\Cache\CacheInterface;
use DeviceDetector\Cache\PSR6Bridge;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;

class DeviceDetector
{
    protected CacheInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $wrappedCache = new ProxyAdapter($cache, 'device.');
        $this->cache = new PSR6Bridge($wrappedCache);
    }

    public function parse(string $userAgent): \DeviceDetector\DeviceDetector
    {
        $dd = new \DeviceDetector\DeviceDetector($userAgent);
        $dd->setCache($this->cache);
        $dd->parse();

        return $dd;
    }
}
