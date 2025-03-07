<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use App\OpenApi;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AccessDenied extends AbstractResponse
{
    protected function getDefaultRef(): string
    {
        return OpenApi::REF_RESPONSE_ACCESS_DENIED;
    }

    protected function getDefaultResponse(): int
    {
        return 403;
    }

    protected function getDefaultDescription(): string
    {
        return 'Access denied (the API key cannot access this resource, or no API key was provided).';
    }
}
