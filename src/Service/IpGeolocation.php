<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\IpGeolocator;
use Exception;
use MaxMind\Db\Reader;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

final class IpGeolocation
{
    private bool $isInitialized = false;

    private ?Reader $reader;

    private ?string $readerShortName;

    private string $attribution = '';

    private CacheInterface $cache;

    public function __construct(CacheItemPoolInterface $psr6Cache)
    {
        $this->cache = new ProxyAdapter($psr6Cache, 'ip_geo.');
    }

    private function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        $readers = [
            IpGeolocator\GeoLite::class,
            IpGeolocator\DbIp::class,
        ];

        foreach ($readers as $reader) {
            /** @var IpGeolocator\IpGeolocatorInterface $reader */
            if ($reader::isAvailable()) {
                $this->reader = $reader::getReader();
                $this->readerShortName = $reader::getReaderShortName();
                $this->attribution = $reader::getAttribution();
                return;
            }
        }

        $this->reader = null;
        $this->readerShortName = null;
        $this->attribution = __(
            'GeoLite database not configured for this installation. See System Administration for instructions.'
        );
    }

    public function getAttribution(): string
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return $this->attribution;
    }

    public function getLocationInfo(string $ip): IpGeolocator\IpResult
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $reader = $this->reader;
        if (null === $reader) {
            throw new RuntimeException('No IP Geolocation reader available.');
        }

        $cacheKey = $this->readerShortName . '_' . str_replace([':', '.'], '_', $ip);

        $ipInfo = $this->cache->get(
            $cacheKey,
            function (CacheItem $item) use ($ip, $reader) {
                /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                $item->expiresAfter(86400 * 7);

                try {
                    $ipInfo = $reader->get($ip);
                    if (!empty($ipInfo)) {
                        return $ipInfo;
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Internal/Reserved IP',
                    ];
                } catch (Exception $e) {
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        );

        return IpGeolocator\IpResult::fromIpInfo($ip, $ipInfo);
    }
}
