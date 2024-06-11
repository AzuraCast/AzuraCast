<?php

declare(strict_types=1);

namespace App\Entity\Attributes;

use Attribute;

/**
 * Marks a class as one whose changes should be logged via the Audit Log.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Auditable
{
}
