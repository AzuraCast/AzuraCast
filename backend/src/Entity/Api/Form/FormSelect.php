<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Form_Select',
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
final class FormSelect
{
    /**
     * @param array<string|int, string|array<string|int, string>> $input
     * @return (FormOption|FormOptionGroup)[]
     */
    public static function fromArray(array $input): array
    {
        $return = [];

        foreach ($input as $outerKey => $outerValue) {
            if (is_array($outerValue)) {
                $options = [];
                foreach ($outerValue as $innerKey => $innerValue) {
                    $options[] = new FormOption(
                        $innerKey,
                        $innerValue
                    );
                }

                $return[] = new FormOptionGroup(
                    $options,
                    (string)$outerKey
                );
            } else {
                $return[] = new FormOption(
                    $outerKey,
                    $outerValue
                );
            }
        }

        return $return;
    }
}
