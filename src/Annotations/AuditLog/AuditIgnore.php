<?php

namespace App\Annotations\AuditLog;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Mark an individual property as one where changes should be ignored.
 *
 * @Annotation
 * @Annotation\Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AuditIgnore
{
}
