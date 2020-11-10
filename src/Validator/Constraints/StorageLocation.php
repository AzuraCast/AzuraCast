<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class StorageLocation extends Constraint
{
    public $message;

    public function __construct($options = null)
    {
        $this->message = __('This storage location could not be validated: %s', '{{ error }}');

        parent::__construct($options);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
