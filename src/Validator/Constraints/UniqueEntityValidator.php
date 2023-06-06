<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Container\EntityManagerAwareTrait;
use Countable;
use DateTimeInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Iterator;
use IteratorAggregate;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function count;
use function get_class;
use function is_array;
use function is_object;

/**
 * Unique Entity Validator checks if one or a set of fields contain unique values.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
final class UniqueEntityValidator extends ConstraintValidator
{
    use EntityManagerAwareTrait;

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        $fields = (array)$constraint->fields;

        if (0 === count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        if (null === $value) {
            return;
        }

        /** @var class-string $className */
        $className = get_class($value);

        $class = $this->em->getClassMetadata($className);

        $criteria = [];
        $hasNullValue = false;

        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        'The field "%s" is not mapped by Doctrine, so it cannot be validated for uniqueness.',
                        $fieldName
                    )
                );
            }

            $fieldValue = $class->reflFields[$fieldName]?->getValue($value);

            if (null === $fieldValue) {
                $hasNullValue = true;
            }

            if ($constraint->ignoreNull && null === $fieldValue) {
                continue;
            }

            $criteria[$fieldName] = $fieldValue;

            if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
                /* Ensure the Proxy is initialized before using reflection to
                 * read its identifiers. This is necessary because the wrapped
                 * getter methods in the Proxy are being bypassed.
                 */
                $this->em->initializeObject($criteria[$fieldName]);
            }
        }

        // validation doesn't fail if one of the fields is null and if null values should be ignored
        if ($hasNullValue && $constraint->ignoreNull) {
            return;
        }

        // skip validation if there are no criteria (this can happen when the
        // "ignoreNull" option is enabled and fields to be checked are null
        if (empty($criteria)) {
            return;
        }

        if (null !== $constraint->entityClass) {
            /* Retrieve repository from given entity name.
             * We ensure the retrieved repository can handle the entity
             * by checking the entity is the same, or subclass of the supported entity.
             */
            $repository = $this->em->getRepository($constraint->entityClass);
            $supportedClass = $repository->getClassName();

            if (!$value instanceof $supportedClass) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        'The "%s" entity repository does not support the "%s" entity. The entity should be '
                        . 'an instance of or extend "%s".',
                        $constraint->entityClass,
                        $class->getName(),
                        $supportedClass
                    )
                );
            }
        } else {
            $repository = $this->em->getRepository(get_class($value));
        }

        $result = $repository->{$constraint->repositoryMethod}($criteria);

        if ($result instanceof IteratorAggregate) {
            $result = $result->getIterator();
        }

        /* If the result is a MongoCursor, it must be advanced to the first
         * element. Rewinding should have no ill effect if $result is another
         * iterator implementation.
         */
        if ($result instanceof Iterator) {
            $result->rewind();
            if ($result instanceof Countable && 1 < count($result)) {
                $result = [$result->current(), $result->current()];
            } else {
                $result = $result->valid() && null !== $result->current() ? [$result->current()] : [];
            }
        } elseif (is_array($result)) {
            reset($result);
        } else {
            $result = null === $result ? [] : [$result];
        }

        /* If no entity matched the query criteria or a single entity matched,
         * which is the same as the entity being validated, the criteria is
         * unique.
         */
        if (!$result || (1 === count($result) && current($result) === $value)) {
            return;
        }

        $errorPath = $constraint->errorPath ?? $fields[0];
        $invalidValue = $criteria[$errorPath] ?? $criteria[$fields[0]];

        $message = __('This value is already used.');

        $this->context->buildViolation($message)
            ->atPath($errorPath)
            ->setParameter('{{ value }}', $this->formatWithIdentifiers($class, $invalidValue))
            ->setInvalidValue($invalidValue)
            ->setCause($result)
            ->addViolation();
    }

    private function formatWithIdentifiers(ClassMetadata $class, mixed $value): string
    {
        if (!is_object($value) || $value instanceof DateTimeInterface) {
            return $this->formatValue($value, self::PRETTY_DATE);
        }

        if (method_exists($value, '__toString')) {
            return (string)$value;
        }

        if ($class->getName() !== $idClass = get_class($value)) {
            // non unique value might be a composite PK that consists of other entity objects
            if ($this->em->getMetadataFactory()->hasMetadataFor($idClass)) {
                $identifiers = $this->em->getClassMetadata($idClass)->getIdentifierValues($value);
            } else {
                // this case might happen if the non unique column has a custom doctrine type and its value is an object
                // in which case we cannot get any identifiers for it
                $identifiers = [];
            }
        } else {
            $identifiers = $class->getIdentifierValues($value);
        }

        if (!$identifiers) {
            return sprintf('object("%s")', $idClass);
        }

        array_walk(
            $identifiers,
            function (&$id, $field): void {
                if (!is_object($id) || $id instanceof DateTimeInterface) {
                    $idAsString = $this->formatValue($id, self::PRETTY_DATE);
                } else {
                    $idAsString = sprintf('object("%s")', get_class($id));
                }

                $id = sprintf('%s => %s', $field, $idAsString);
            }
        );

        return sprintf('object("%s") identified by (%s)', $idClass, implode(', ', $identifiers));
    }
}
