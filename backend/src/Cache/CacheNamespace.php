<?php

declare(strict_types=1);

namespace App\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

enum CacheNamespace: string
{
    case AutoCue = 'autocue';
    case AzuraRelay = 'azurarelay';
    case Centrifugo = 'centrifugo';
    case DeviceDetector = 'device_detector';
    case Doctrine = 'doctrine';
    case IpGeo = 'ip_geo';
    case Messages = 'messages';
    case NowPlaying = 'now_playing';
    case QueueLog = 'queue_log';
    case RateLimit = 'rate_limit';
    case Session = 'session';
    case SyncStatus = 'sync_last_run';

    public function withNamespace(CacheItemPoolInterface $psr6Cache): CacheItemPoolInterface
    {
        if ($psr6Cache instanceof NamespacedPoolInterface) {
            return $psr6Cache->withSubNamespace($this->value);
        }

        return new ProxyAdapter(
            $psr6Cache,
            $this->value
        );
    }
}
