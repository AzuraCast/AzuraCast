<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use App\OpenApi;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class SuccessWithDownload extends Success
{
    protected function getDefaultRef(): string
    {
        return OpenApi::REF_RESPONSE_SUCCESS_WITH_DOWNLOAD;
    }
}
