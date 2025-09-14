<?php

declare(strict_types=1);

namespace App\Entity\Api;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_BatchResult',
    required: ['*'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean'
        ),
    ],
    type: 'object'
)]
abstract class BatchResult implements JsonSerializable
{
    #[OA\Property(
        items: new OA\Items(
            type: 'string'
        )
    )]
    public array $errors = [];

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
        ];
    }
}
