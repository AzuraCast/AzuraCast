<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NewRecord',
    type: 'object'
)]
final readonly class NewRecord extends Status
{
    #[OA\Property(
        items: new OA\Items(type: 'string', example: 'http://localhost/api/record/1')
    )]
    public array $links;

    public function __construct(
        bool $success = true,
        string $message = 'Changes saved successfully.',
        ?string $formattedMessage = null,
        array $links = []
    ) {
        parent::__construct($success, $message, $formattedMessage);

        $this->links = $links;
    }
}
