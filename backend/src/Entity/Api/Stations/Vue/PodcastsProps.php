<?php

declare(strict_types=1);

namespace App\Entity\Api\Stations\Vue;

use App\Entity\Api\Form\NestedFormOptions;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Stations_Vue_PodcastsProps',
    required: ['*'],
    type: 'object'
)]
final readonly class PodcastsProps
{
    public function __construct(
        #[OA\Property(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )]
        public array $languageOptions,
        #[OA\Property]
        public NestedFormOptions $categoriesOptions
    ) {
    }
}
