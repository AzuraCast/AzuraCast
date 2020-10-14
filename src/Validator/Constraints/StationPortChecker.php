<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class StationPortChecker extends Constraint
{
    public $message;

    public function __construct($options = null)
    {
        $this->message = __('The port %s is in use by another station.', '{{ port }}');

        parent::__construct($options);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
