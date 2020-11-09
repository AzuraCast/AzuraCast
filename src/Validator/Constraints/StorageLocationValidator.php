<?php

namespace App\Validator\Constraints;

use App\Entity;
use App\Radio\Configuration;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StorageLocationValidator extends ConstraintValidator
{
    protected Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function validate($storageLocation, Constraint $constraint): void
    {
        if (!$constraint instanceof StorageLocation) {
            throw new UnexpectedTypeException($constraint, StorageLocation::class);
        }

        if (!($storageLocation instanceof Entity\StorageLocation)) {
            throw new UnexpectedTypeException($storageLocation, Entity\StorageLocation::class);
        }

        try {
            $storageLocation->validate();
        } catch (\Exception $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ error }}', $e->getMessage())
                ->addViolation();
        }
    }
}
