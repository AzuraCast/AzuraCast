<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use Meilisearch\Client;

final class Index
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly Client $client,
        private readonly StorageLocation $storageLocation,
        private readonly string $indexUid
    ) {
    }

    public function configure(): void
    {
        $indexSettings = [
            'primaryKey' => 'id',
            'filterableAttributes' => [
                'playlists',
                'is_requestable',
                'is_on_demand',
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

        foreach ($this->customFieldRepo->getFieldIds() as $fieldId => $fieldShortCode) {
            $indexSettings['sortableAttributes'][] = 'custom_field_' . $fieldId;
        }

        $this->client->updateIndex(
            $this->indexUid,
            $indexSettings
        );
    }

    /** @return Station[] */
    private function iterateStations(): iterable
    {
        return $this->em->createQuery(
            <<<'DQL'
            SELECT s FROM App\Entity\Station s
            WHERE s.media_storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $this->storageLocation)
            ->toIterable();
    }
}
