<?php

namespace App\Annotations\AuditLog;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Indicate a property or method that should be used to determine the "identifier"
 *   for an auditable record.
 *
 * @Annotation
 * @Annotation\Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AuditIdentifier
{
}
