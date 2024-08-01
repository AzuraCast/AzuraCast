<?php

declare(strict_types=1);

namespace App\Entity\Attributes;

use Attribute;

/**
 * Mark an individual property as one where changes should be ignored.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class AuditIgnore
{
}
