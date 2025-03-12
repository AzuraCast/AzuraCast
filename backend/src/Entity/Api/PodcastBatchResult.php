<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_PodcastBatchResult',
    required: ['*'],
    type: 'object'
)]
final class PodcastBatchResult extends BatchResult
{
    /**
     * @var array<array{
     *   id: string,
     *   title: string
     * }>
     */
    #[OA\Property(
        items: new OA\Items(
            required: [
                'id',
                'title',
            ],
            properties: [
                new OA\Property(
                    property: 'id',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'title',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    public array $episodes = [];

    #[OA\Property(
        items: new OA\Items(
            type: PodcastEpisode::class
        )
    )]
    public ?array $records = null;

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'episodes' => $this->episodes,
            'records' => $this->records,
        ];
    }
}
