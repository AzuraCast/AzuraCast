<?php

namespace App\Validator\Constraints;

use App\Entity;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StorageLocationValidator extends ConstraintValidator
{
    public function __construct(
        protected Configuration $configuration,
        protected EntityManagerInterface $em,
    ) {
    }

    public function validate($storageLocation, Constraint $constraint): void
    {
        if (!$constraint instanceof StorageLocation) {
            throw new UnexpectedTypeException($constraint, StorageLocation::class);
        }

        if (!($storageLocation instanceof Entity\StorageLocation)) {
            throw new UnexpectedTypeException($storageLocation, Entity\StorageLocation::class);
        }

        // Ensure this storage location validates.
        try {
            $storageLocation->validate();
        } catch (Exception $e) {
            $message = __(
                'Storage location %s could not be validated: %s',
                '{{ storageLocation }}',
                '{{ error }}'
            );

            $this->context->buildViolation($message)
                ->setParameter('{{ storageLocation }}', (string)$storageLocation)
                ->setParameter('{{ error }}', $e->getMessage())
                ->addViolation();
        }

        // Ensure it's not a duplicate of other storage locations.
        $qb = $this->em->createQueryBuilder()
            ->select('sl')
            ->from(Entity\StorageLocation::class, 'sl')
            ->where('sl.type = :type')
            ->setParameter('type', $storageLocation->getType())
            ->andWhere('sl.adapter = :adapter')
            ->setParameter('adapter', $storageLocation->getAdapter());

        if (null !== $storageLocation->getId()) {
            $qb->andWhere('sl.id != :id')
                ->setParameter('id', $storageLocation->getId());
        }

        $storageLocationUri = $storageLocation->getUri();

        /** @var Entity\StorageLocation $row */
        foreach ($qb->getQuery()->toIterable() as $row) {
            if ($row->getUri() === $storageLocationUri) {
                $message = __(
                    'Storage location %s already exists.',
                    '{{ storageLocation }}',
                );

                $this->context->buildViolation($message)
                    ->setParameter('{{ storageLocation }}', (string)$storageLocation)
                    ->addViolation();

                break;
            }
        }
    }
}
