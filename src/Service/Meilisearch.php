<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Repository\CustomFieldRepository;
use App\Entity\StorageLocation;
use App\Environment;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Meilisearch\Client;

final class Meilisearch
{
    public const BATCH_SIZE = 50;

    public function __construct(
        private readonly Environment $environment,
        private readonly GuzzleClient $httpClient,
        private readonly CustomFieldRepository $customFieldRepo,
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

    public function setupIndex(StorageLocation $storageLocation): void
    {
        $indexSettings = [
            'primaryKey' => 'id',
            'filterableAttributes' => [
                'playlists',
                'is_requestable',
                'is_on_demand'
            ],
            'sortableAttributes' => [
                'path',
                'mtime',
                'length',
                'title',
                'artist',
                'album',
                'genre',
                'isrc',
            ],
        ];

        foreach($this->customFieldRepo->getFieldIds() as $fieldId => $fieldShortCode) {
            $indexSettings['sortableAttributes'][] = 'custom_field_'.$fieldId;
        }

        $client = $this->getClient();
        $client->updateIndex(
            self::getIndexId($storageLocation),
            $indexSettings
        );
    }

    public function addToIndex(
        StorageLocation $storageLocation,
        array $ids
    ): void {

    }

    public static function getIndexId(StorageLocation $storageLocation): string
    {
        return 'media_'.$storageLocation->getIdRequired();
    }
}
