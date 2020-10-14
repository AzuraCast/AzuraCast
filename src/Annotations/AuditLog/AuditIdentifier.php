<?php

namespace App\Annotations\AuditLog;

use Doctrine\Common\Annotations\Annotation;

/**
 * Indicate a property or method that should be used to determine the "identifier"
 *   for an auditable record.
 *
 * @Annotation
 * @Annotation\Target("METHOD")
 */
class AuditIdentifier
{
}
