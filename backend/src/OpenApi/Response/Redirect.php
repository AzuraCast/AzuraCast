<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use Attribute;
use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\Header;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use OpenApi\Attributes\XmlContent;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Redirect extends Response
{
    public function __construct(
        object|string|null $ref = null,
        int|string|null $response = 302,
        ?string $description = 'A temporary redirect to the correct resource.',
        ?array $headers = null,
        MediaType|JsonContent|array|Attachable|XmlContent|null $content = null,
        ?array $links = null,
        ?array $x = null,
        ?array $attachables = null
    ) {
        $headers ??= [];
        $headers[] = new Header(
            header: 'location',
            description: 'The current URL of the resource.',
            schema: new Schema(
                type: 'string'
            )
        );

        parent::__construct($ref, $response, $description, $headers, $content, $links, $x, $attachables);
    }
}
