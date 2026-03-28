<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\UserLoginToken;
use OpenApi\Attributes as OA;

#[
    OA\Schema(
        schema: 'Api_Admin_NewLoginTokenResponse',
        required: ['*'],
        type: 'object',
    ),
]
final readonly class NewLoginTokenResponse
{
    public function __construct(
        #[OA\Property(example: true)]
        public bool $success,
        #[OA\Property(example: 'Changes saved successfully.')]
        public string $message,
        #[OA\Property(example: '<b>Changes saved successfully.</b>')]
        public string $formatted_message,
        #[OA\Property]
        public UserLoginToken $record,
        #[OA\Property(
            type: 'object',
            readOnly: true,
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )]
        public array $links
    ) {
    }
}
