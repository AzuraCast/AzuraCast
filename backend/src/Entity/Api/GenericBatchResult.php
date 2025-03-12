<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_GenericBatchResult',
    required: ['*'],
    type: 'object'
)]
final class GenericBatchResult extends BatchResult
{
    /**
     * @var array<array{
     *   id: int,
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
                    type: 'integer',
                    format: 'int64'
                ),
                new OA\Property(
                    property: 'title',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    public array $records = [];

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'records' => $this->records,
        ];
    }
}
