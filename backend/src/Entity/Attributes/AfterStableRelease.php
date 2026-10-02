<?php

declare(strict_types=1);

namespace App\Entity\Attributes;

use Attribute;

/**
 * Sort a database migration after the #[StableMigration] marker of the given stable version.
 * Used for migrations that were merged after that version was released, but have an older timestamp.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AfterStableRelease
{
    public function __construct(
        public string $version
    ) {
    }
}
