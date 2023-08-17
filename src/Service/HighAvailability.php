<?php

declare(strict_types=1);

namespace App\Service;

use App\Cache\DatabaseCache;
use App\Container\EnvironmentAwareTrait;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;

final class HighAvailability
{
    use EnvironmentAwareTrait;

    private const ACTIVE_SERVER_KEY = 'active_server';
    private const LOCK_TTL = 90;

    public function __construct(
        private readonly DatabaseCache $dbCache
    ) {
    }

    public function isActiveServer(): bool
    {
        if ($this->environment->isTesting()) {
            return true;
        }

        $cacheItem = $this->dbCache->getItem(self::ACTIVE_SERVER_KEY);
        $serverIdentifier = $this->getServerIdentifier();

        if ($cacheItem->isHit() && $serverIdentifier !== $cacheItem->get()) {
            return false;
        }

        $cacheItem->set($serverIdentifier);
        $cacheItem->expiresAfter(self::LOCK_TTL);

        $this->dbCache->save($cacheItem);

        return true;
    }

    public function getServerIdentifier(): string
    {
        $identifierPath = $this->environment->getTempDirectory() . '/server_id';

        if (file_exists($identifierPath)) {
            $serverIdentifier = trim(file_get_contents($identifierPath) ?: '');
            if ('' !== $serverIdentifier) {
                return $serverIdentifier;
            }
        }

        $uuidFactory = clone Uuid::getFactory();
        $nodeProvider = new RandomNodeProvider();
        $serverIdentifier = $uuidFactory->uuid6($nodeProvider->getNode())->toString();

        (new Filesystem())->dumpFile(
            $identifierPath,
            $serverIdentifier
        );

        return $serverIdentifier;
    }
}
