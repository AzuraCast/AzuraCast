<?php

declare(strict_types=1);

namespace App\Entity\Api\Stations\Vue;

use App\Entity\CustomField;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Stations_Vue_FilesProps',
    required: ['*'],
    type: 'object'
)]
final readonly class FilesProps
{
    public function __construct(
        #[OA\Property(
            items: new OA\Items(
                required: [
                    'id',
                    'name',
                ],
                properties: [
                    new OA\Property(
                        property: 'id',
                        type: 'integer',
                        format: 'int64'
                    ),
                    new OA\Property(
                        property: 'name',
                        type: 'string'
                    ),
                ],
                type: 'object'
            )
        )]
        public array $initialPlaylists,
        #[OA\Property(
            items: new OA\Items(ref: CustomField::class)
        )]
        public array $customFields,
        #[OA\Property(
            items: new OA\Items(type: 'string')
        )]
        public array $validMimeTypes,
        #[OA\Property]
        public bool $supportsImmediateQueue
    ) {
    }
}
