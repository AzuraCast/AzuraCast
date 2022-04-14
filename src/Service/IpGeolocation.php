<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\SupportedLocales;
use App\Service\IpGeolocator;
use MaxMind\Db\Reader;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class IpGeolocation
{
    protected bool $isInitialized = false;

    protected ?Reader $reader;

    protected ?string $readerShortName;

    protected string $attribution = '';

    protected CacheInterface $cache;

    public function __construct(CacheItemPoolInterface $psr6Cache)
    {
        $this->cache = new ProxyAdapter($psr6Cache, 'ip_geo.');
    }

    protected function initialize(): void
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

    /**
     * @return mixed[]
     */
    public function getLocationInfo(string $ip, SupportedLocales $locale): array
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $reader = $this->reader;
        if (null === $reader) {
            return [
                'status' => 'error',
                'message' => $this->getAttribution(),
            ];
        }

        return (array)$reader->get($ip);
    }
}
