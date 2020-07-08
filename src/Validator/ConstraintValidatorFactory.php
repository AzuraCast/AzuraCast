<?php
namespace App\Validator;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ExpressionValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    protected array $validators = [];

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(Constraint $constraint)
    {
        $className = $constraint->validatedBy();

        if (!isset($this->validators[$className])) {
            if ('validator.expression' === $className) {
                $this->validators[$className] = new ExpressionValidator;
            } elseif ($this->container->has($className)) {
                $this->validators[$className] = $this->container->get($className);
            } else {
                $this->validators[$className] = new $className();
            }
        }

        return $this->validators[$className];
    }
}
