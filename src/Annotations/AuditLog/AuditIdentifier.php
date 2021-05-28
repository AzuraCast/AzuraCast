<?php

namespace App\Annotations\AuditLog;

use Attribute;

/**
 * Indicate a property or method that should be used to determine the "identifier"
 * for an auditable record.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AuditIdentifier
{
}
