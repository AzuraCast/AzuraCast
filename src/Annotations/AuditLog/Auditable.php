<?php

namespace App\Annotations\AuditLog;

use Attribute;

/**
 * Marks a class as one whose changes should be logged via the Audit Log.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Auditable
{
}
