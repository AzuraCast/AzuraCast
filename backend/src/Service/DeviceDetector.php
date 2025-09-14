<?php

declare(strict_types=1);

namespace App\Service;

use App\Cache\CacheNamespace;
use App\Service\DeviceDetector\DeviceResult;
use DeviceDetector\DeviceDetector as ParentDeviceDetector;
use Psr\Cache\CacheItemPoolInterface;

final class DeviceDetector
{
    private CacheItemPoolInterface $psr6Cache;

    private ParentDeviceDetector $dd;

    /** @var array<string, DeviceResult> */
    private array $deviceResults = [];

    public function __construct(
        CacheItemPoolInterface $psr6Cache
    ) {
        $this->psr6Cache = CacheNamespace::DeviceDetector->withNamespace($psr6Cache);

        $this->dd = new ParentDeviceDetector();
    }

    public function parse(string $userAgent): DeviceResult
    {
        $userAgentHash = md5($userAgent);

        if (isset($this->deviceResults[$userAgentHash])) {
            return $this->deviceResults[$userAgentHash];
        }

        $cacheItem = $this->psr6Cache->getItem($userAgentHash);

        if (!$cacheItem->isHit()) {
            $this->dd->setUserAgent($userAgent);
            $this->dd->parse();

            $cacheItem->set(DeviceResult::fromDeviceDetector($userAgent, $this->dd));

            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            $cacheItem->expiresAfter(86400 * 7);

            $this->psr6Cache->saveDeferred($cacheItem);
        }

        $deviceResult = $cacheItem->get();
        assert($deviceResult instanceof DeviceResult);

        $this->deviceResults[$userAgentHash] = $deviceResult;

        return $deviceResult;
    }

    public function saveCache(): bool
    {
        return $this->psr6Cache->commit();
    }
}
