<?php

namespace App\Normalizer;

use App\Normalizer\Attributes\DeepNormalize;
use App\Normalizer\Exception\NoGetterAvailableException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

use function is_array;

final class DoctrineEntityNormalizer extends AbstractNormalizer implements NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public const NORMALIZE_TO_IDENTIFIERS = 'form_mode';

    public const CLASS_METADATA = 'class_metadata';
    public const ASSOCIATION_MAPPINGS = 'association_mappings';

    private readonly Inflector $inflector;

    public function __construct(
        private readonly EntityManagerInterface $em,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        array $defaultContext = []
    ) {
        $defaultContext[self::ALLOW_EXTRA_ATTRIBUTES] = false;
        parent::__construct($classMetadataFactory, $nameConverter, $defaultContext);

        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * Replicates the "toArray" functionality previously present in Doctrine 1.
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Cannot normalize non-object.');
        }

        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object, $format, $context);
        }

        $context[self::CLASS_METADATA] = $this->em->getClassMetadata($object::class);

        $props = $this->getAllowedAttributes($object, $context);

        $return_arr = [];
        if ($props) {
            foreach ($props as $property) {
                $attribute = $property->getName();

                try {
                    $value = $this->getAttributeValue($object, $attribute, $format, $context);

                    /** @var callable|null $callback */
                    $callback = $context[self::CALLBACKS][$attribute]
                        ?? $this->defaultContext[self::CALLBACKS][$attribute]
                        ?? null;

                    if ($callback) {
                        $value = $callback($value, $object, $attribute, $format, $context);
                    }

                    $return_arr[$attribute] = $value;
                } catch (NoGetterAvailableException) {
                }
            }
        }

        return $return_arr;
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
        /** @var T $object */
        $object = $this->instantiateObject($data, $type, $context, new ReflectionClass($type), false, $format);

        $type = get_class($object);

        $context[self::CLASS_METADATA] = $this->em->getMetadataFactory()->getMetadataFor($type);
        $context[self::ASSOCIATION_MAPPINGS] = [];

        if ($context[self::CLASS_METADATA]->associationMappings) {
            foreach ($context[self::CLASS_METADATA]->associationMappings as $mapping_name => $mapping_info) {
                $entity = $mapping_info['targetEntity'];

                if (isset($mapping_info['joinTable'])) {
                    $context[self::ASSOCIATION_MAPPINGS][$mapping_info['fieldName']] = [
                        'type' => 'many',
                        'entity' => $entity,
                        'is_owning_side' => ($mapping_info['isOwningSide'] == 1),
                    ];
                } elseif (isset($mapping_info['joinColumns'])) {
                    foreach ($mapping_info['joinColumns'] as $col) {
                        $col_name = $col['name'];
                        $col_name = $context[self::CLASS_METADATA]->fieldNames[$col_name] ?? $col_name;

                        $context[self::ASSOCIATION_MAPPINGS][$mapping_name] = [
                            'name' => $col_name,
                            'type' => 'one',
                            'entity' => $entity,
                        ];
                    }
                }
            }
        }

        foreach ((array)$data as $attribute => $value) {
            /** @var callable|null $callback */
            $callback = $context[self::CALLBACKS][$attribute]
                ?? $this->defaultContext[self::CALLBACKS][$attribute]
                ?? null;

            if ($callback) {
                $value = $callback($value, $object, $attribute, $format, $context);
            }

            $this->setAttributeValue($object, $attribute, $value, $format, $context);
        }

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $this->isEntity($data);
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, string $format = null): bool
    {
        return $this->isEntity($type);
    }

    /**
     * @param object|class-string $classOrObject
     * @param array $context
     * @param bool $attributesAsString
     *
     */
    protected function getAllowedAttributes(
        $classOrObject,
        array $context,
        bool $attributesAsString = false
    ): array|false {
        $meta = $this->classMetadataFactory?->getMetadataFor($classOrObject)?->getAttributesMetadata();
        if (null === $meta) {
            throw new \RuntimeException('Class metadata factory not specified.');
        }

        $props_raw = (new ReflectionClass($classOrObject))->getProperties(
            ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED
        );
        $props = [];
        foreach ($props_raw as $prop_raw) {
            $props[$prop_raw->getName()] = $prop_raw;
        }

        $props = array_intersect_key($meta, $props);

        $tmpGroups = $context[self::GROUPS] ?? $this->defaultContext[self::GROUPS] ?? null;
        $groups = (is_array($tmpGroups) || is_scalar($tmpGroups)) ? (array)$tmpGroups : false;

        $allowedAttributes = [];
        foreach ($props as $attributeMetadata) {
            $name = $attributeMetadata->getName();

            if (
                (false === $groups || array_intersect($attributeMetadata->getGroups(), $groups)) &&
                $this->isAllowedAttribute($classOrObject, $name, null, $context)
            ) {
                $allowedAttributes[] = $attributesAsString ? $name : $attributeMetadata;
            }
        }

        return $allowedAttributes;
    }

    private function getAttributeValue(
        object $object,
        string $prop_name,
        string $format = null,
        array $context = []
    ): mixed {
        $form_mode = $context[self::NORMALIZE_TO_IDENTIFIERS] ?? false;

        if (isset($context[self::CLASS_METADATA]->associationMappings[$prop_name])) {
            $deepNormalizeAttrs = (new ReflectionClass($object))->getProperty($prop_name)->getAttributes(
                DeepNormalize::class
            );
            if (!empty($deepNormalizeAttrs)) {
                /** @var DeepNormalize $deepNormalize */
                $deepNormalize = current($deepNormalizeAttrs)->newInstance();

                $deep = $deepNormalize->getDeepNormalize();
            } else {
                $deep = false;
            }

            if (!$deep) {
                throw new NoGetterAvailableException(
                    sprintf(
                        'Deep normalization disabled for property %s.',
                        $prop_name
                    )
                );
            }

            $prop_val = $this->getProperty($object, $prop_name);

            if ($prop_val instanceof Collection) {
                $return_val = [];
                if (count($prop_val) > 0) {
                    /** @var object $val_obj */
                    foreach ($prop_val as $val_obj) {
                        if ($form_mode) {
                            $id_field = $this->em->getClassMetadata($val_obj::class)->identifier;

                            if ($id_field && count($id_field) === 1) {
                                $return_val[] = $this->getProperty($val_obj, $id_field[0]);
                            }
                        } else {
                            $return_val[] = $this->normalizer->normalize($val_obj, $format, $context);
                        }
                    }
                }
                return $return_val;
            }

            return $this->normalizer->normalize($prop_val, $format, $context);
        }

        $value = $this->getProperty($object, $prop_name);
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return $value;
    }

    private function getProperty(object $entity, string $key): mixed
    {
        // Default to "getStatus", "getConfig", etc...
        $getter_method = $this->getMethodName($key, 'get');
        if (method_exists($entity, $getter_method)) {
            return $entity->{$getter_method}();
        }

        // but also allow "isEnabled" instead of "getIsEnabled"
        $raw_method = $this->getMethodName($key);
        if (method_exists($entity, $raw_method)) {
            return $entity->{$raw_method}();
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
     * @param string $field
     * @param mixed $value
     * @param string|null $format
     * @param array $context
     */
    private function setAttributeValue(
        object $object,
        string $field,
        mixed $value,
        ?string $format = null,
        array $context = []
    ): void {
        if (isset($context[self::ASSOCIATION_MAPPINGS][$field])) {
            // Handle a mapping to another entity.
            $mapping = $context[self::ASSOCIATION_MAPPINGS][$field];

            if ('one' === $mapping['type']) {
                if (empty($value)) {
                    $this->setProperty($object, $field, null);
                } else {
                    /** @var class-string $entity */
                    $entity = $mapping['entity'];
                    if (($field_item = $this->em->find($entity, $value)) instanceof $entity) {
                        $this->setProperty($object, $field, $field_item);
                    }
                }
            } elseif ($mapping['is_owning_side']) {
                $collection = $this->getProperty($object, $field);

                if ($collection instanceof Collection) {
                    $collection->clear();

                    if ($value) {
                        foreach ((array)$value as $field_id) {
                            /** @var class-string $entity */
                            $entity = $mapping['entity'];

                            $field_item = $this->em->find($entity, $field_id);
                            if ($field_item instanceof $entity) {
                                $collection->add($field_item);
                            }
                        }
                    }
                }
            }
        } else {
            $this->setProperty($object, $field, $value);
        }
    }

    private function setProperty(object $entity, string $key, mixed $value): void
    {
        $method_name = $this->getMethodName($key, 'set');
        if (!method_exists($entity, $method_name)) {
            return;
        }

        $entity->$method_name($value);
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
}
