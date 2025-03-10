<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Form_Option',
    required: [
        'value',
        'text',
    ],
    type: 'object'
)]
final readonly class FormOption
{
    public function __construct(
        #[OA\Property]
        public string|int $value,
        #[OA\Property]
        public string $text,
        #[OA\Property]
        public ?string $description = null
    ) {
    }
}
