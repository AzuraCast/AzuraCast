<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\DeviceDetector\DeviceResult;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

final class DeviceDetector
{
    private CacheInterface $cache;

    private \DeviceDetector\DeviceDetector $dd;

    /** @var array<string, DeviceResult> */
    private array $deviceResults = [];

    public function __construct(
        CacheItemPoolInterface $psr6Cache
    ) {
        $this->cache = new ProxyAdapter($psr6Cache, 'device_detector.');

        $this->dd = new \DeviceDetector\DeviceDetector();
    }

    public function parse(string $userAgent): DeviceResult
    {
        $userAgentHash = md5($userAgent);

        if (isset($this->deviceResults[$userAgentHash])) {
            return $this->deviceResults[$userAgentHash];
        }

        $deviceResult = $this->cache->get(
            $userAgentHash,
            function (CacheItem $item) use ($userAgent) {
                /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                $item->expiresAfter(86400 * 7);

                $this->dd->setUserAgent($userAgent);
                $this->dd->parse();

                return DeviceResult::fromDeviceDetector($userAgent, $this->dd);
            }
        );

        $this->deviceResults[$userAgentHash] = $deviceResult;

        return $deviceResult;
    }
}
