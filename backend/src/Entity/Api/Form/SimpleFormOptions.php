<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Form_SimpleOptions',
    type: 'array',
    items: new OA\Items(
        ref: FormOption::class
    )
)]
final readonly class SimpleFormOptions extends AbstractOptions
{
    /**
     * @param array<string|int, string> $input
     */
    public static function fromArray(array $input): self
    {
        $return = [];

        foreach ($input as $outerKey => $outerValue) {
            $return[] = new FormOption(
                $outerKey,
                $outerValue
            );
        }

        return new self($return);
    }
}
