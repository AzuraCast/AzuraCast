<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\StorageLocation;
use App\Environment;
use App\Service\Meilisearch\Index;
use DI\FactoryInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Meilisearch\Client;

final readonly class Meilisearch
{
    public const BATCH_SIZE = 100;

    public function __construct(
        private Environment $environment,
        private GuzzleClient $httpClient,
        private FactoryInterface $factory
    ) {
    }

    public function isSupported(): bool
    {
        return $this->environment->isDocker();
    }

    public function getClient(): Client
    {
        static $client;

        if (!$this->isSupported()) {
            throw new \RuntimeException('This feature is not supported on this installation.');
        }

        if (!isset($client)) {
            $psrFactory = new HttpFactory();
            $client = new Client(
                'http://localhost:6070',
                $this->environment->getMeiliMasterKey(),
                $this->httpClient,
                requestFactory: $psrFactory,
                streamFactory: $psrFactory
            );
        }

        return $client;
    }

    public function getIndex(StorageLocation $storageLocation): Index
    {
        $client = $this->getClient();

        return $this->factory->make(
            Index::class,
            [
                'storageLocation' => $storageLocation,
                'indexClient' => $client->index(self::getIndexUid($storageLocation)),
            ]
        );
    }

    public static function getIndexUid(StorageLocation $storageLocation): string
    {
        return 'media_' . $storageLocation->getIdRequired();
    }
}
