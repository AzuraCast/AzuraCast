<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\StorageLocation;
use App\Validator\Constraints\StorageLocation as StorageLocationConstraint;
use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class StorageLocationValidator extends ConstraintValidator
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StorageLocationConstraint) {
            throw new UnexpectedTypeException($constraint, StorageLocation::class);
        }

        if (!($value instanceof StorageLocation)) {
            throw new UnexpectedTypeException($value, StorageLocation::class);
        }

        // Ensure this storage location validates.
        try {
            $adapter = $this->storageLocationRepo->getAdapter($value);
            $adapter->validate();
        } catch (Exception $e) {
            $message = sprintf(
                __('Storage location %s could not be validated: %s'),
                '{{ storageLocation }}',
                '{{ error }}'
            );

            $this->context->buildViolation($message)
                ->setParameter('{{ storageLocation }}', (string)$value)
                ->setParameter('{{ error }}', $e->getMessage())
                ->addViolation();
        }

        // Ensure it's not a duplicate of other storage locations.
        $qb = $this->em->createQueryBuilder()
            ->select('sl')
            ->from(StorageLocation::class, 'sl')
            ->where('sl.type = :type')
            ->setParameter('type', $value->getType())
            ->andWhere('sl.adapter = :adapter')
            ->setParameter('adapter', $value->getAdapter());

        if (null !== $value->getId()) {
            $qb->andWhere('sl.id != :id')
                ->setParameter('id', $value->getId());
        }

        $storageLocationUri = $value->getUri();

        /** @var StorageLocation $row */
        foreach ($qb->getQuery()->toIterable() as $row) {
            if ($row->getUri() === $storageLocationUri) {
                $message = sprintf(
                    __('Storage location %s already exists.'),
                    '{{ storageLocation }}',
                );

                $this->context->buildViolation($message)
                    ->setParameter('{{ storageLocation }}', (string)$value)
                    ->addViolation();

                break;
            }
        }
    }
}
