<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class StreamerPassword extends Constraint
{
    public $message = 'Password cannot contain the following characters: {{ chars }}';
}
