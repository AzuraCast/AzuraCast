<?php

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
class UniqueEntity extends Constraint
{
    public $entityClass = null;
    public $repositoryMethod = 'findBy';
    public $fields = [];
    public $errorPath = null;
    public $ignoreNull = true;

    /**
     * {@inheritdoc}
     *
     * @param array|string $fields the combination of fields that must contain unique values or a set of options
     */
    public function __construct(
        array|string $fields,
        string $entityClass = null,
        string $repositoryMethod = null,
        string $errorPath = null,
        bool $ignoreNull = null,
        array $groups = null,
        $payload = null,
        array $options = []
    ) {
        if (is_array($fields) && is_string(key($fields))) {
            $options = array_merge($fields, $options);
        } else {
            $options['fields'] = $fields;
        }

        parent::__construct($options, $groups, $payload);

        $this->entityClass = $entityClass ?? $this->entityClass;
        $this->repositoryMethod = $repositoryMethod ?? $this->repositoryMethod;
        $this->errorPath = $errorPath ?? $this->errorPath;
        $this->ignoreNull = $ignoreNull ?? $this->ignoreNull;
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['fields'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getDefaultOption(): string
    {
        return 'fields';
    }
}
