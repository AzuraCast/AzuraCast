<?php

declare(strict_types=1);

namespace App\Entity\Attributes;

use Attribute;

/**
 * Mark a database migration as the last migration before a stable version was tagged.
 */
#[Attribute(Attribute::TARGET_CLASS | ATTRIBUTE::IS_REPEATABLE)]
final class StableMigration
{
    public function __construct(
        public string $version
    ) {
    }
}
