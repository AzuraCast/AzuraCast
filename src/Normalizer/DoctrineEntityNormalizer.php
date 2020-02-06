<?php
namespace App\Normalizer;

use App\Exception\NoGetterAvailableException;
use App\Normalizer\Annotation\DeepNormalize;
use DateTime;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Proxy\Proxy;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerInterface;
use function is_array;

class DoctrineEntityNormalizer extends AbstractNormalizer
{
    use ObjectToPopulateTrait;

    public const NORMALIZE_TO_IDENTIFIERS = 'form_mode';

    public const CLASS_METADATA = 'class_metadata';
    public const ASSOCIATION_MAPPINGS = 'association_mappings';

    /** @var EntityManager */
    protected $em;

    /** @var SerializerInterface|NormalizerInterface|DenormalizerInterface */
    protected $serializer;

    /** @var Reader */
    protected $annotationReader;

    /**
     * @param EntityManager $em
     * @param Reader|null $annotationReader
     * @param ClassMetadataFactoryInterface|null $classMetadataFactory
     * @param NameConverterInterface|null $nameConverter
     * @param array $defaultContext
     */
    public function __construct(
        EntityManager $em,
        Reader $annotationReader = null,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        array $defaultContext = []
    ) {
        /** @var AnnotationDriver $metadata_driver */
        $metadata_driver = $em->getConfiguration()->getMetadataDriverImpl();

        $annotationReader = $annotationReader ?? $metadata_driver->getReader();
        $classMetadataFactory = $classMetadataFactory ?? new ClassMetadataFactory(
                new AnnotationLoader($annotationReader)
            );

        $defaultContext[self::ALLOW_EXTRA_ATTRIBUTES] = false;

        parent::__construct($classMetadataFactory, $nameConverter, $defaultContext);

        $this->em = $em;
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param DenormalizerInterface|NormalizerInterface|SerializerInterface $serializer
     */
    public function setSerializer($serializer): void
    {
        if (!$serializer instanceof DenormalizerInterface || !$serializer instanceof NormalizerInterface) {
            throw new InvalidArgumentException('Expected a serializer that also implements DenormalizerInterface and NormalizerInterface.');
        }

        $this->serializer = $serializer;
    }

    /**
     * Replicates the "toArray" functionality previously present in Doctrine 1.
     *
     * @param mixed $object
     * @param null $format
     * @param array $context
     *
     * @return mixed
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object, $format, $context);
        }

        $context[self::CLASS_METADATA] = $this->em->getClassMetadata(get_class($object));

        $props = $this->getAllowedAttributes($object, $context);

        $return_arr = [];
        if ($props) {
            foreach ($props as $property) {
                $attribute = $property->getName();

                try {
                    $value = $this->getAttributeValue($object, $attribute, $format, $context);

                    /** @var callable|null $callback */
                    $callback = $context[self::CALLBACKS][$attribute] ?? $this->defaultContext[self::CALLBACKS][$attribute] ?? $this->callbacks[$attribute] ?? null;
                    if ($callback) {
                        $value = $callback($value, $object, $attribute, $format, $context);
                    }

                    $return_arr[$attribute] = $value;
                } catch (NoGetterAvailableException $e) {
                    continue;
                }
            }
        }

        return $return_arr;
    }

    /**
     * Replicates the "fromArray" functionality previously present in Doctrine 1.
     *
     * @param mixed $data
     * @param string $class
     * @param string|null $format
     * @param array $context
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = $this->instantiateObject($data, $class, $context, new ReflectionClass($class), false, $format);

        $class = get_class($object);

        $context[self::CLASS_METADATA] = $this->em->getMetadataFactory()->getMetadataFor($class);
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
            $callback = $context[self::CALLBACKS][$attribute] ?? $this->defaultContext[self::CALLBACKS][$attribute] ?? $this->callbacks[$attribute] ?? null;
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
    public function supportsNormalization($data, $format = null)
    {
        return $this->_isEntity($data);
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->_isEntity($type);
    }

    /**
     * @param object|string $classOrObject
     * @param array $context
     * @param bool $attributesAsString
     *
     * @return bool|string[]|AttributeMetadataInterface[]
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        $meta = $this->classMetadataFactory->getMetadataFor($classOrObject)->getAttributesMetadata();

        $reflect = new ReflectionClass($classOrObject);
        $props_raw = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
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

    /**
     * @param object $object
     * @param string $prop_name
     * @param null $format
     * @param array $context
     *
     * @return mixed
     */
    protected function getAttributeValue($object, $prop_name, $format = null, array $context = [])
    {
        $form_mode = $context[self::NORMALIZE_TO_IDENTIFIERS] ?? false;

        if (isset($context[self::CLASS_METADATA]->associationMappings[$prop_name])) {
            $annotation = $this->annotationReader->getPropertyAnnotation(
                new ReflectionProperty(get_class($object), $prop_name),
                DeepNormalize::class
            );

            $deep = ($annotation instanceof DeepNormalize)
                ? $annotation->getDeepNormalize()
                : false;

            if (!$deep) {
                throw new NoGetterAvailableException(sprintf('Deep normalization disabled for property %s.',
                    $prop_name));
            }

            $prop_val = $this->_get($object, $prop_name);

            if ($prop_val instanceof Collection) {
                $return_val = [];
                if (count($prop_val) > 0) {
                    foreach ($prop_val as $val_obj) {
                        if ($form_mode) {
                            $obj_meta = $this->em->getClassMetadata(get_class($val_obj));
                            $id_field = $obj_meta->identifier;

                            if ($id_field && count($id_field) === 1) {
                                $return_val[] = $this->_get($val_obj, $id_field[0]);
                            }
                        } else {
                            $return_val[] = $this->serializer->normalize($val_obj, $format, $context);
                        }
                    }
                }
                return $return_val;
            }

            return $this->serializer->normalize($prop_val, $format, $context);
        }

        return $this->_get($object, $prop_name);
    }

    /**
     * @param object $entity
     * @param string $key
     *
     * @return mixed|null
     */
    protected function _get($entity, $key)
    {
        // Default to "getStatus", "getConfig", etc...
        $getter_method = $this->_getMethodName($key, 'get');
        if (method_exists($entity, $getter_method)) {
            return $entity->{$getter_method}();
        }

        // but also allow "isEnabled" instead of "getIsEnabled"
        $raw_method = $this->_getMethodName($key);
        if (method_exists($entity, $raw_method)) {
            return $entity->{$raw_method}();
        }

        throw new NoGetterAvailableException(sprintf('No getter is available for property %s.', $key));
    }

    /**
     * Converts "getvar_name_blah" to "getVarNameBlah".
     *
     * @param string $var
     * @param string $prefix
     *
     * @return string
     */
    protected function _getMethodName($var, $prefix = ''): string
    {
        return Inflector::camelize(($prefix ? $prefix . '_' : '') . $var);
    }

    /**
     * @param object $object
     * @param string $field
     * @param mixed $value
     * @param null $format
     * @param array $context
     */
    protected function setAttributeValue($object, $field, $value, $format = null, array $context = []): void
    {
        if (isset($context[self::ASSOCIATION_MAPPINGS][$field])) {
            // Handle a mapping to another entity.
            $mapping = $context[self::ASSOCIATION_MAPPINGS][$field];

            if ('one' === $mapping['type']) {
                if (empty($value)) {
                    $this->_set($object, $field, null);
                } elseif (($field_item = $this->em->find($mapping['entity'], $value)) instanceof $mapping['entity']) {
                    $this->_set($object, $field, $field_item);
                }
            } elseif ($mapping['is_owning_side']) {
                $collection = $this->_get($object, $field);

                if ($collection instanceof Collection) {
                    $collection->clear();

                    if ($value) {
                        foreach ((array)$value as $field_id) {
                            if (($field_item = $this->em->find($mapping['entity'],
                                    $field_id)) instanceof $mapping['entity']) {
                                $collection->add($field_item);
                            }
                        }
                    }
                }
            }
        } else {
            $this->_set($object, $field, $value);
        }
    }

    /**
     * @param object $entity
     * @param string $key
     * @param mixed $value
     *
     * @return mixed|null
     */
    protected function _set($entity, $key, $value)
    {
        $method_name = $this->_getMethodName($key, 'set');

        if (!method_exists($entity, $method_name)) {
            return null;
        }

        $method = new ReflectionMethod(get_class($entity), $method_name);
        $first_param = $method->getParameters()[0];

        if ($first_param->hasType()) {
            $first_param_type = $first_param->getType()->getName();

            switch ($first_param_type) {
                case 'DateTime':
                    if (!($value instanceof DateTime)) {
                        if (!is_numeric($value)) {
                            $value = strtotime($value . ' UTC');
                        }

                        $dt = new DateTime;
                        $dt->setTimestamp($value);
                        $value = $dt;
                    }
                    break;

                case 'int':
                    if ($value === null) {
                        if (!$first_param->allowsNull()) {
                            $value = 0;
                        }
                    } else {
                        $value = (int)$value;
                    }
                    break;

                case 'float':
                    if ($value === null) {
                        if (!$first_param->allowsNull()) {
                            $value = 0.0;
                        }
                    } else {
                        $value = (float)$value;
                    }
                    break;

                case 'bool':
                    if ($value === null) {
                        if (!$first_param->allowsNull()) {
                            $value = false;
                        }
                    } else {
                        $value = (bool)$value;
                    }
                    break;
            }
        }

        return $entity->$method_name($value);
    }

    /**
     * @param object|string $class
     *
     * @return bool
     */
    protected function _isEntity($class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        } elseif (!is_string($class)) {
            return false;
        }

        if (!class_exists($class)) {
            return false;
        }

        return !$this->em->getMetadataFactory()->isTransient($class);
    }
}
