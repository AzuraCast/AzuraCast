<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use App\OpenApi;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Success extends AbstractResponse
{
    protected function getDefaultRef(): string
    {
        return OpenApi::REF_RESPONSE_SUCCESS;
    }

    protected function getDefaultResponse(): int
    {
        return 200;
    }

    protected function getDefaultDescription(): string
    {
        return 'Success';
    }
}
