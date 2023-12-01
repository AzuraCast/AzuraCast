<?php

namespace App\Normalizer;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Normalizer\Attributes\DeepNormalize;
use App\Normalizer\Exception\NoGetterAvailableException;
use ArrayObject;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final class DoctrineEntityNormalizer extends AbstractObjectNormalizer
{
    private const CLASS_METADATA = 'class_metadata';
    private const ASSOCIATION_MAPPINGS = 'association_mappings';

    private readonly Inflector $inflector;

    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        array $defaultContext = []
    ) {
        $defaultContext[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] = true;

        parent::__construct(
            classMetadataFactory: $classMetadataFactory,
            defaultContext: $defaultContext
        );

        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * Replicates the "toArray" functionality previously present in Doctrine 1.
     *
     * @return array|string|int|float|bool|ArrayObject<int, mixed>|null
     */
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        if (!is_object($object)) {
            throw new InvalidArgumentException('Cannot normalize non-object.');
        }

        $context = $this->addDoctrineContext($object::class, $context);

        return parent::normalize($object, $format, $context);
    }

    /**
     * Replicates the "fromArray" functionality previously present in Doctrine 1.
     *
     * @template T as object
     * @param mixed $data
     * @param class-string<T> $type
     * @param string|null $format
     * @param array $context
     * @return T
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): object
    {
        $context = $this->addDoctrineContext($type, $context);

        return parent::denormalize($data, $type, $format, $context);
    }

    /**
     * @param class-string<object> $className
     * @param array $context
     * @return array
     */
    private function addDoctrineContext(
        string $className,
        array $context
    ): array {
        $context[self::CLASS_METADATA] =  $this->em->getClassMetadata($className);
        $context[self::ASSOCIATION_MAPPINGS] = [];

        if ($context[self::CLASS_METADATA]->associationMappings) {
            foreach ($context[self::CLASS_METADATA]->associationMappings as $mappingName => $mappingInfo) {
                $entity = $mappingInfo['targetEntity'];

                if (isset($mappingInfo['joinTable'])) {
                    $context[self::ASSOCIATION_MAPPINGS][$mappingInfo['fieldName']] = [
                        'type' => 'many',
                        'entity' => $entity,
                        'is_owning_side' => ($mappingInfo['isOwningSide'] == 1),
                    ];
                } elseif (isset($mappingInfo['joinColumns'])) {
                    foreach ($mappingInfo['joinColumns'] as $col) {
                        $colName = $col['name'];
                        $colName = $context[self::CLASS_METADATA]->fieldNames[$colName] ?? $colName;

                        $context[self::ASSOCIATION_MAPPINGS][$mappingName] = [
                            'name' => $colName,
                            'type' => 'one',
                            'entity' => $entity,
                        ];
                    }
                }
            }
        }

        return $context;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $this->isEntity($data);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $this->isEntity($type);
    }

    /**
     * @param object|class-string<object> $classOrObject
     * @param array $context
     * @param bool $attributesAsString
     * @return array|false
     */
    protected function getAllowedAttributes(
        $classOrObject,
        array $context,
        bool $attributesAsString = false
    ): array|false {
        $groups = $this->getGroups($context);
        if (empty($groups)) {
            return false;
        }

        return parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);
    }

    protected function extractAttributes(object $object, string $format = null, array $context = []): array
    {
        $rawProps = (new ReflectionClass($object))->getProperties(
            ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED
        );

        $props = [];
        foreach ($rawProps as $rawProp) {
            $props[] = $rawProp->getName();
        }

        return array_filter(
            $props,
            fn($attribute) => $this->isAllowedAttribute($object, $attribute, $format, $context)
        );
    }

    /**
     * @param object|class-string<object> $classOrObject
     * @param string $attribute
     * @param string|null $format
     * @param array $context
     * @return bool
     * @throws ReflectionException
     */
    protected function isAllowedAttribute(
        object|string $classOrObject,
        string $attribute,
        string $format = null,
        array $context = []
    ): bool {
        if (!parent::isAllowedAttribute($classOrObject, $attribute, $format, $context)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($classOrObject);
        if (!$reflectionClass->hasProperty($attribute)) {
            return false;
        }

        if (isset($context[self::CLASS_METADATA]->associationMappings[$attribute])) {
            if (!$this->supportsDeepNormalization($reflectionClass, $attribute)) {
                return false;
            }
        }

        return $this->hasGetter($reflectionClass, $attribute);
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param string $attribute
     * @return bool
     */
    private function hasGetter(ReflectionClass $reflectionClass, string $attribute): bool
    {
        // Default to "getStatus", "getConfig", etc...
        $getterMethod = $this->getMethodName($attribute, 'get');
        if ($reflectionClass->hasMethod($getterMethod)) {
            return true;
        }

        $rawMethod = $this->getMethodName($attribute);
        return $reflectionClass->hasMethod($rawMethod);
    }

    protected function getAttributeValue(
        object $object,
        string $attribute,
        string $format = null,
        array $context = []
    ): mixed {
        if (isset($context[self::CLASS_METADATA]->associationMappings[$attribute])) {
            if (!$this->supportsDeepNormalization(new ReflectionClass($object), $attribute)) {
                throw new NoGetterAvailableException(
                    sprintf(
                        'Deep normalization disabled for property %s.',
                        $attribute
                    )
                );
            }
        }

        $value = $this->getProperty($object, $attribute);
        if ($value instanceof Collection) {
            $value = $value->getValues();
        }

        return $value;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param string $attribute
     * @return bool
     * @throws ReflectionException
     */
    private function supportsDeepNormalization(ReflectionClass $reflectionClass, string $attribute): bool
    {
        $deepNormalizeAttrs = $reflectionClass->getProperty($attribute)->getAttributes(
            DeepNormalize::class
        );

        if (empty($deepNormalizeAttrs)) {
            return false;
        }

        /** @var DeepNormalize $deepNormalize */
        $deepNormalize = current($deepNormalizeAttrs)->newInstance();
        return $deepNormalize->getDeepNormalize();
    }

    private function getProperty(object $entity, string $key): mixed
    {
        // Default to "getStatus", "getConfig", etc...
        $getterMethod = $this->getMethodName($key, 'get');
        if (method_exists($entity, $getterMethod)) {
            return $entity->{$getterMethod}();
        }

        // but also allow "isEnabled" instead of "getIsEnabled"
        $rawMethod = $this->getMethodName($key);
        if (method_exists($entity, $rawMethod)) {
            return $entity->{$rawMethod}();
        }

        throw new NoGetterAvailableException(sprintf('No getter is available for property %s.', $key));
    }

    /**
     * Converts "getvar_name_blah" to "getVarNameBlah".
     */
    private function getMethodName(string $var, string $prefix = ''): string
    {
        return $this->inflector->camelize(($prefix ? $prefix . '_' : '') . $var);
    }

    /**
     * @param object $object
     * @param string $attribute
     * @param mixed $value
     * @param string|null $format
     * @param array $context
     */
    protected function setAttributeValue(
        object $object,
        string $attribute,
        mixed $value,
        ?string $format = null,
        array $context = []
    ): void {
        if (isset($context[self::ASSOCIATION_MAPPINGS][$attribute])) {
            // Handle a mapping to another entity.
            $mapping = $context[self::ASSOCIATION_MAPPINGS][$attribute];

            if ('one' === $mapping['type']) {
                if (empty($value)) {
                    $this->setProperty($object, $attribute, null);
                } else {
                    /** @var class-string $entity */
                    $entity = $mapping['entity'];
                    if (($fieldItem = $this->em->find($entity, $value)) instanceof $entity) {
                        $this->setProperty($object, $attribute, $fieldItem);
                    }
                }
            } elseif ($mapping['is_owning_side']) {
                $collection = $this->getProperty($object, $attribute);

                if ($collection instanceof Collection) {
                    $collection->clear();

                    if ($value) {
                        foreach ((array)$value as $fieldId) {
                            /** @var class-string $entity */
                            $entity = $mapping['entity'];

                            $fieldItem = $this->em->find($entity, $fieldId);
                            if ($fieldItem instanceof $entity) {
                                $collection->add($fieldItem);
                            }
                        }
                    }
                }
            }
        } else {
            $methodName = $this->getMethodName($attribute, 'set');

            $reflClass = new ReflectionClass($object);
            if (!$reflClass->hasMethod($methodName)) {
                return;
            }

            // If setter parameter is a special class, normalize to it.
            $methodParams = $reflClass->getMethod($methodName)->getParameters();
            $parameter = $methodParams[0];

            if (null === $value && $parameter->allowsNull()) {
                $value = null;
            } else {
                $value = $this->denormalizeParameter(
                    $reflClass,
                    $parameter,
                    $attribute,
                    $value,
                    $this->createChildContext($context, $attribute, $format),
                    $format
                );
            }

            $this->setProperty($object, $attribute, $value);
        }
    }

    private function setProperty(
        object $entity,
        string $attribute,
        mixed $value
    ): void {
        $methodName = $this->getMethodName($attribute, 'set');
        if (!method_exists($entity, $methodName)) {
            return;
        }

        $entity->$methodName($value);
    }

    private function isEntity(mixed $class): bool
    {
        if (is_object($class)) {
            $class = ClassUtils::getClass($class);
        }

        if (!is_string($class) || !class_exists($class)) {
            return false;
        }

        return !$this->em->getMetadataFactory()->isTransient($class);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
