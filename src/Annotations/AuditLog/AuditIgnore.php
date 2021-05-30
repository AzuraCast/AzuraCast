<?php

namespace App\Annotations\AuditLog;

use Attribute;

/**
 * Mark an individual property as one where changes should be ignored.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AuditIgnore
{
}
