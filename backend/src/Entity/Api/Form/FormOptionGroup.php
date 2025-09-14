<?php

declare(strict_types=1);

namespace App\Entity\Api\Form;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Form_OptionGroup',
    required: [
        'options',
        'label',
    ],
    type: 'object'
)]
final readonly class FormOptionGroup
{
    /** @param FormOption[] $options */
    public function __construct(
        #[OA\Property(
            items: new OA\Items(
                ref: FormOption::class
            )
        )]
        public array $options,
        #[OA\Property]
        public string $label,
    ) {
    }
}
