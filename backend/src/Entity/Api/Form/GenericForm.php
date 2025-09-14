<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use OpenApi\Attributes as OA;
use stdClass;

#[OA\Schema(
    schema: 'Api_GenericForm',
    type: 'object',
    additionalProperties: new OA\AdditionalProperties(
        type: '{}'
    )
)]
final class GenericForm extends stdClass
{
}
