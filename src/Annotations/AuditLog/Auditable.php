<?php

namespace App\Annotations\AuditLog;

use Doctrine\Common\Annotations\Annotation;

/**
 * Marks a class as one whose changes should be logged via the Audit Log.
 *
 * @Annotation
 * @Annotation\Target("CLASS")
 */
class Auditable
{
}
