<?php

declare(strict_types=1);

namespace App\TypeScript;

use Attribute;

/**
 * Ignore a specific enum case.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class IgnoreCase
{
}
