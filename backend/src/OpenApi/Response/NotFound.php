<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use App\OpenApi;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class NotFound extends AbstractResponse
{
    protected function getDefaultRef(): string
    {
        return OpenApi::REF_RESPONSE_NOT_FOUND;
    }

    protected function getDefaultResponse(): int
    {
        return 404;
    }

    protected function getDefaultDescription(): string
    {
        return 'The resource specified was not found (check ID parameters).';
    }
}
