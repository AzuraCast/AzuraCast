<?php

namespace App\Annotations\AuditLog;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Marks a class as one whose changes should be logged via the Audit Log.
 *
 * @Annotation
 * @Annotation\Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Auditable
{
}
