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

use Attribute;
use Symfony\Component\Validator\Constraint;

use function is_array;
use function is_string;

/**
 * Constraint for the Unique Entity validator.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class UniqueEntity extends Constraint
{
    /** @var class-string|null */
    public ?string $entityClass = null;

    /**
     * {@inheritDoc}
     *
     * @param class-string|null $entityClass
     * @param array|string $fields the combination of fields that must contain unique values or a set of options
     */
    public function __construct(
        ?string $entityClass = null,
        public array|string $fields = [],
        public ?string $repositoryMethod = 'findBy',
        public ?string $errorPath = null,
        public ?bool $ignoreNull = null,
        array $groups = null,
        $payload = null,
        array $options = []
    ) {
        $this->entityClass = $entityClass ?? $this->entityClass;

        if (is_array($fields) && is_string(key($fields))) {
            $options = array_merge($fields, $options);
        } else {
            $options['fields'] = $fields;
        }

        parent::__construct($options, $groups, $payload);
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['fields'];
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getDefaultOption(): string
    {
        return 'fields';
    }
}
