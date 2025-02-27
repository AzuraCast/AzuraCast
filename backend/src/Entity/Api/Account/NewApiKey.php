<?php

declare(strict_types=1);

namespace App\Entity\Api\Account;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Account_NewApiKey',
    required: ['*'],
    type: 'object'
)]
final readonly class NewApiKey
{
    public function __construct(
        #[OA\Property(
            description: 'The newly generated API key.',
            readOnly: true,
        )]
        public string $key
    ) {
    }
}
