<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Status;
use App\Entity\UserLoginToken;
use OpenApi\Attributes as OA;

#[
    OA\Schema(
        schema: 'Api_Admin_NewLoginTokenResponse',
        required: ['*'],
        type: 'object',
    ),
]
final readonly class NewLoginTokenResponse extends Status
{
    public function __construct(
        bool $success,
        string $message,
        ?string $formattedMessage,
        #[OA\Property]
        public UserLoginToken $record,
        #[OA\Property(
            type: 'object',
            readOnly: true,
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )]
        public array $links
    ) {
        parent::__construct($success, $message, $formattedMessage);
    }
}
