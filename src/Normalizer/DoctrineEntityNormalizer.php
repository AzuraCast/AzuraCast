<?php
namespace App\Normalizer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerInterface;

class DoctrineEntityNormalizer extends AbstractNormalizer
{
    use ObjectToPopulateTrait;

    const DEEP_NORMALIZE = 'deep';
    const NORMALIZE_TO_IDENTIFIERS = 'form_mode';

    const CLASS_METADATA = 'class_metadata';
    const ASSOCIATION_MAPPINGS = 'association_mappings';

    /** @var EntityManager */
    protected $em;

    /** @var SerializerInterface|NormalizerInterface|DenormalizerInterface */
    protected $serializer;

    /**
     * DoctrineEntityNormalizer constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    /**
     * @param DenormalizerInterface|NormalizerInterface|SerializerInterface $serializer
     */
    public function setSerializer($serializer): void
    {
        if (!$serializer instanceof DenormalizerInterface || !$serializer instanceof NormalizerInterface) {
            throw new \InvalidArgumentException('Expected a serializer that also implements DenormalizerInterface and NormalizerInterface.');
        }

        $this->serializer = $serializer;
    }

    /**
     * Replicates the "toArray" functionality previously present in Doctrine 1.
     *
     * @param mixed $object
     * @param null $format
     * @param array $context
     * @return array|bool|float|int|string
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object, $format, $context);
        }

        $context[self::CLASS_METADATA] = $this->em->getClassMetadata(get_class($object));

        $reflect = new \ReflectionClass($object);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

        $return_arr = [];
        if ($props) {
            foreach ($props as $property) {
                $attribute = $property->getName();

                $value = $this->getAttributeValue($object, $attribute, $format, $context);

                /**
                 * @var $callback callable|null
                 */
                $callback = $context[self::CALLBACKS][$attribute] ?? $this->defaultContext[self::CALLBACKS][$attribute] ?? $this->callbacks[$attribute] ?? null;
                if ($callback) {
                    $value = $callback($value, $object, $attribute, $format, $context);
                }

                if (null !== $value) {
                    $return_arr[$attribute] = $value;
                }
            }
        }

        return $return_arr;
    }

    /**
     * @param $object
     * @param $prop_name
     * @param null $format
     * @param array $context
     * @return array|bool|float|int|string|null
     */
    protected function getAttributeValue($object, $prop_name, $format = null, array $context = array())
    {
        $deep = $context[self::DEEP_NORMALIZE] ?? true;
        $form_mode = $context[self::NORMALIZE_TO_IDENTIFIERS] ?? false;

        $prop_info = $context[self::CLASS_METADATA]->fieldMappings[$prop_name] ?? [];

        try {
            $prop_val = $this->_get($object, $prop_name);
        } catch(\App\Exception\NoGetterAvailable $e) {
            return null;
        }

        if (is_array($prop_val)) {
            return $prop_val;
        }

        if (!is_object($prop_val)) {
            if ('array' === $prop_info['type']) {
                return (array)$prop_val;
            }
            return (string)$prop_val;
        }

        if ($prop_val instanceof \DateTime) {
            return $prop_val->getTimestamp();
        }

        if ($deep) {
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

        return null;
    }

    /**
     * Replicates the "fromArray" functionality previously present in Doctrine 1.
     *
     * @param mixed $data
     * @param string $class
     * @param null $format
     * @param array $context
     * @return object|void
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $object = $this->instantiateObject($data, $class, $context, new \ReflectionClass($class), false, $format);

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
            /**
             * @var $callback callable|null
             */
            $callback = $context[self::CALLBACKS][$attribute] ?? $this->defaultContext[self::CALLBACKS][$attribute] ?? $this->callbacks[$attribute] ?? null;
            if ($callback) {
                $value = $callback($value, $object, $attribute, $format, $context);
            }

            $this->setAttributeValue($object, $attribute, $value, $format, $context);
        }
    }

    /**
     * @param $object
     * @param $field
     * @param $value
     * @param null $format
     * @param array $context
     */
    protected function setAttributeValue($object, $field, $value, $format = null, array $context = array())
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
            } else if ($mapping['is_owning_side']) {
                $collection = $this->_get($object, $field);

                if ($collection instanceof Collection) {
                    $collection->clear();

                    if ($value) {
                        foreach ((array)$value as $field_id) {
                            if (($field_item = $this->em->find($mapping['entity'], $field_id)) instanceof $mapping['entity']) {
                                $collection->add($field_item);
                            }
                        }
                    }
                }
            }
        } else {
            // Handle a scalar value that should possibly be converted.
            $field_info = $context[self::CLASS_METADATA]->fieldMappings[$field] ?? [];

            switch ($field_info['type']) {
                case 'datetime':
                case 'date':
                    if (!($value instanceof \DateTime)) {
                        if ($value) {
                            if (!is_numeric($value)) {
                                $value = strtotime($value . ' UTC');
                            }

                            $value = \DateTime::createFromFormat(\DateTime::ATOM, gmdate(\DateTime::ATOM, (int)$value));
                        } else {
                            $value = null;
                        }
                    }
                    break;

                case 'string':
                    if (is_string($value) && $field_info['length'] && strlen($value) > $field_info['length']) {
                        $value = substr($value, 0, $field_info['length']);
                    }
                    break;

                case 'decimal':
                case 'float':
                    if ($value !== null) {
                        if (is_numeric($value)) {
                            $value = (float)$value;
                        } elseif (empty($value)) {
                            $value = ($field_info['nullable']) ? NULL : 0.0;
                        }
                    }
                    break;

                case 'integer':
                case 'smallint':
                case 'bigint':
                    if ($value !== null) {
                        $value = (int)$value;
                    }
                    break;

                case 'boolean':
                    if ($value !== null) {
                        $value = (bool)$value;
                    }
                    break;
            }

            $this->_set($object, $field, $value);
        }
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
     * @param $class
     * @return bool
     */
    protected function _isEntity($class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        } else if (!is_string($class)) {
            return false;
        }

        if (!class_exists($class)) {
            return false;
        }

        return !$this->em->getMetadataFactory()->isTransient($class);
    }

    /**
     * @param $entity
     * @param $key
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

        throw new \App\Exception\NoGetterAvailable(sprintf('No getter is available for property %s.', $key));
    }

    /**
     * @param $entity
     * @param $key
     * @param $value
     * @return mixed|null
     */
    protected function _set($entity, $key, $value)
    {
        $method_name = $this->_getMethodName($key, 'set');

        return (method_exists($entity, $method_name))
            ? $entity->$method_name($value)
            : null;
    }

    /**
     * Converts "getvar_name_blah" to "getVarNameBlah".
     *
     * @param $var
     * @param string $prefix
     * @return string
     */
    protected function _getMethodName($var, $prefix = ''): string
    {
        return \Doctrine\Common\Inflector\Inflector::camelize(($prefix ? $prefix.'_' : '') . $var);
    }
}
