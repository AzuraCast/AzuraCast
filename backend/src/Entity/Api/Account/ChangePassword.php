<?php

declare(strict_types=1);

namespace App\Entity\Api\Account;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'Api_Account_ChangePassword',
    required: ['*'],
    type: 'object'
)]
final readonly class ChangePassword
{
    public function __construct(
        #[
            OA\Property(
                description: 'The current account password.',
                writeOnly: true
            ),
            Assert\NotBlank,
        ]
        public string $current_password,
        #[
            OA\Property(
                description: 'The new account password.',
                writeOnly: true
            ),
            Assert\NotBlank
        ]
        public string $new_password
    ) {
    }
}
