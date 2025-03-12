<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use App\OpenApi;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class GenericError extends AbstractResponse
{
    protected function getDefaultRef(): string
    {
        return OpenApi::REF_RESPONSE_GENERIC_ERROR;
    }

    protected function getDefaultResponse(): int
    {
        return 500;
    }

    protected function getDefaultDescription(): string
    {
        return 'A generic error occurred on the server.';
    }
}
