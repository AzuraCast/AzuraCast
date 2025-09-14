<?php

declare(strict_types=1);

namespace App\Entity\Api\Account;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Account_TwoFactorStatus',
    required: ['*'],
    type: 'object'
)]
final readonly class TwoFactorStatus
{
    public function __construct(
        #[OA\Property(
            description: 'The current two-factor status for this account.',
            readOnly: true
        )]
        public bool $two_factor_enabled,
    ) {
    }
}
