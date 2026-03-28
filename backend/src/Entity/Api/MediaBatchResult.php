<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_MediaBatchResult',
    required: ['*'],
    type: 'object'
)]
final class MediaBatchResult extends BatchResult
{
    /** @var string[] */
    #[OA\Property(
        items: new OA\Items(
            type: 'string'
        )
    )]
    public array $files = [];

    /** @var string[] */
    #[OA\Property(
        items: new OA\Items(
            type: 'string'
        )
    )]
    public array $directories = [];

    #[OA\Property(
        items: new OA\Items(
            type: 'any'
        )
    )]
    public ?array $responseRecord = null;

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'files' => $this->files,
            'directories' => $this->directories,
            'record' => $this->responseRecord,
        ];
    }
}
