<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use Attribute;
use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\XmlContent;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class GenericError extends Response
{
    public function __construct(
        object|string|null $ref = null,
        int|string|null $response = 500,
        ?string $description = 'A generic error occurred on the server.',
        ?array $headers = null,
        MediaType|JsonContent|array|Attachable|XmlContent|null $content = null,
        ?array $links = null,
        ?array $x = null,
        ?array $attachables = null
    ) {
        if (null === $content && null === $ref) {
            $ref = '#/components/responses/GenericError';
        }

        parent::__construct($ref, $response, $description, $headers, $content, $links, $x, $attachables);
    }
}
