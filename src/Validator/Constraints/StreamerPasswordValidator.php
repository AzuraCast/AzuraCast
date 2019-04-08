<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class StreamerPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof StreamerPassword) {
            throw new UnexpectedTypeException($constraint, StreamerPassword::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $avoid_chars = ['@', ':', ',', '#'];

        if (0 < count(array_intersect(str_split($value), $avoid_chars))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ chars }}', implode(', ', $avoid_chars))
                ->addViolation();
        }
    }
}
