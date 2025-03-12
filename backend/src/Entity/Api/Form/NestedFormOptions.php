<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Form_NestedOptions',
    type: 'array',
    items: new OA\Items(
        anyOf: [
            new OA\Schema(
                ref: FormOption::class
            ),
            new OA\Schema(
                ref: FormOptionGroup::class
            ),
        ]
    )
)]
final readonly class NestedFormOptions extends AbstractOptions
{
    /**
     * @param array<string|int, string|array<string|int, string>> $input
     */
    public static function fromArray(array $input): self
    {
        $return = [];

        foreach ($input as $outerKey => $outerValue) {
            if (is_array($outerValue)) {
                $return[] = new FormOptionGroup(
                    SimpleFormOptions::fromArray($outerValue)->toArray(),
                    (string)$outerKey
                );
            } else {
                $return[] = new FormOption(
                    $outerKey,
                    $outerValue
                );
            }
        }

        return new self($return);
    }
}
