<?php

namespace App\Annotations\AuditLog;

use Doctrine\Common\Annotations\Annotation;

/**
 * Mark an individual property as one where changes should be ignored.
 *
 * @Annotation
 * @Annotation\Target("PROPERTY")
 */
class AuditIgnore
{
}
