<?php
namespace App\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

class Repository extends EntityRepository
{
    /**
     * Generate an array result of all records.
     *
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public function fetchArray($cached = true, $order_by = null, $order_dir = 'ASC')
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('e')
            ->from($this->_entityName, 'e');

        if ($order_by) {
            $qb->orderBy('e.' . str_replace('e.', '', $order_by), $order_dir);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Generic dropdown builder function (can be overridden for specialized use cases).
     *
     * @param bool $add_blank
     * @param \Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     * @return array
     */
    public function fetchSelect($add_blank = false, \Closure $display = null, $pk = 'id', $order_by = 'name')
    {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== false) {
            $select[''] = ($add_blank === true) ? 'Select...' : $add_blank;
        }

        // Build query for records.
        $qb = $this->_em->createQueryBuilder()->from($this->_entityName, 'e');

        if ($display === null) {
            $qb->select('e.' . $pk)->addSelect('e.name')->orderBy('e.' . $order_by, 'ASC');
        } else {
            $qb->select('e')->orderBy('e.' . $order_by, 'ASC');
        }

        $results = $qb->getQuery()->getArrayResult();

        // Assemble select values and, if necessary, call $display callback.
        foreach ((array)$results as $result) {
            $key = $result[$pk];
            $value = ($display === null) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }

        return $select;
    }

    /**
     * FromArray (A Doctrine 1 Classic)
     *
     * @param object|Entity $entity
     * @param array $source
     */
    public function fromArray($entity, array $source)
    {
        $metadata = $this->_getMetadata($entity);

        $meta = $metadata['meta'];
        $mappings = $metadata['mappings'];

        foreach ((array)$source as $field => $value) {
            if (isset($mappings[$field])) {
                $mapping = $mappings[$field];

                switch ($mapping['type']) {
                    case "one_id":
                        $entity_field = $mapping['name'];
                        $entity_id = $mapping['ids'][0];

                        if (empty($value)) {
                            $this->_set($entity, $field, null);
                            $this->_set($entity, $entity_field, null);
                        } else {
                            if ($value != $this->_get($entity, $field)) {
                                $obj_class = $mapping['entity'];
                                $obj = $this->_em->find($obj_class, $value);

                                if ($obj instanceof $obj_class) {
                                    $this->_set($entity, $field, $this->_get($obj, $entity_id));
                                    $this->_set($entity, $entity_field, $obj);
                                }
                            }
                        }
                        break;

                    case "one_entity":
                        $entity_id = $mapping['ids'][0];
                        $id_field = $mapping['name'];

                        if (empty($value)) {
                            $this->_set($entity, $field, null);
                            $this->_set($entity, $id_field, null);
                        } else {
                            if ($this->_get($value, $entity_id) != $this->_get($entity, $field)) {
                                $this->_set($entity, $field, $value);
                                $this->_set($entity, $id_field, $this->_get($value, $entity_id));
                            }
                        }
                        break;

                    case "many":
                        $obj_class = $mapping['entity'];

                        if ($mapping['is_owning_side']) {
                            /** @var Collection $collection */
                            $collection = $this->_get($entity, $field);

                            $collection->clear();

                            if ($value) {
                                foreach ((array)$value as $field_id) {
                                    if (($field_item = $this->_em->find($obj_class, (int)$field_id)) instanceof $obj_class) {
                                        $collection->add($field_item);
                                    }
                                }
                            }
                        } else {
                            $foreign_field = $mapping['mappedBy'];

                            if (count($this->_get($entity, $field)) > 0) {
                                foreach ($this->_get($entity, $field) as $record) {
                                    /** @var Collection $collection */
                                    $collection = $this->_get($record, $foreign_field);
                                    $collection->removeElement($entity);

                                    $this->_em->persist($record);
                                }
                            }

                            foreach ((array)$value as $field_id) {
                                $record = $this->_em->find($obj_class, (int)$field_id);

                                if ($record instanceof $obj_class) {
                                    /** @var Collection $collection */
                                    $collection = $this->_get($record, $foreign_field);
                                    $collection->add($entity);

                                    $this->_em->persist($record);
                                }
                            }

                            $this->_em->flush();
                        }
                        break;
                }
            } else {
                if (!isset($meta->fieldMappings[$field])) {
                    $field_info = [];
                } else {
                    $field_info = $meta->fieldMappings[$field];
                }

                switch ($field_info['type']) {
                    case "datetime":
                    case "date":
                        if (!($value instanceof \DateTime)) {
                            if ($value) {
                                if (!is_numeric($value)) {
                                    $value = strtotime($value . ' UTC');
                                }

                                $value = \DateTime::createFromFormat(\DateTime::ISO8601,
                                    gmdate(\DateTime::ISO8601, (int)$value));
                            } else {
                                $value = null;
                            }
                        }
                        break;

                    case "string":
                        if ($field_info['length'] && strlen($value) > $field_info['length']) {
                            $value = substr($value, 0, $field_info['length']);
                        }
                        break;

                    case "decimal":
                    case "float":
                        if ($value !== null) {
                            if (is_numeric($value)) {
                                $value = (float)$value;
                            } elseif (empty($value)) {
                                $value = ($field_info['nullable']) ? NULL : 0.0;
                            }
                        }
                        break;

                    case "integer":
                    case "smallint":
                    case "bigint":
                        if ($value !== null) {
                            $value = (int)$value;
                        }
                        break;

                    case "boolean":
                        if ($value !== null) {
                            $value = (bool)$value;
                        }
                        break;
                }

                $this->_set($entity, $field, $value);
            }
        }
    }

    /**
     * ToArray (A Doctrine 1 Classic)
     *
     * @param object|Entity $entity
     * @param bool $deep Iterate through collections associated with this item.
     * @param bool $form_mode Return values in a format suitable for ZendForm setDefault function.
     * @return array
     */
    public function toArray($entity, $deep = false, $form_mode = false)
    {
        $return_arr = [];

        $class_meta = $this->_em->getClassMetadata(get_class($entity));

        $reflect = new \ReflectionClass($entity);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

        if ($props) {
            foreach ($props as $property) {
                $property->setAccessible(true);
                $prop_name = $property->getName();
                $prop_val = $property->getValue($entity);

                if (isset($class_meta->fieldMappings[$prop_name])) {
                    $prop_info = $class_meta->fieldMappings[$prop_name];
                } else {
                    $prop_info = [];
                }

                if (is_array($prop_val)) {
                    $return_arr[$prop_name] = $prop_val;
                } else {
                    if (!is_object($prop_val)) {
                        if ($prop_info['type'] == "array") {
                            $return_arr[$prop_name] = (array)$prop_val;
                        } else {
                            $return_arr[$prop_name] = (string)$prop_val;
                        }
                    } else {
                        if ($prop_val instanceof \DateTime) {
                            $return_arr[$prop_name] = $prop_val->getTimestamp();
                        } else {
                            if ($deep) {
                                if ($prop_val instanceof \Doctrine\Common\Collections\Collection) {
                                    $return_val = [];
                                    if (count($prop_val) > 0) {
                                        foreach ($prop_val as $val_obj) {
                                            if ($form_mode) {
                                                $obj_meta = $this->_em->getClassMetadata(get_class($val_obj));
                                                $id_field = $obj_meta->identifier;

                                                if ($id_field && count($id_field) == 1) {
                                                    $return_val[] = $this->_get($val_obj, $id_field[0]);
                                                }
                                            } else {
                                                $return_val[] = $this->toArray($val_obj, false);
                                            }
                                        }
                                    }

                                    $return_arr[$prop_name] = $return_val;
                                } else {
                                    $return_arr[$prop_name] = $this->toArray($prop_val, false);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $return_arr;
    }

    /**
     * Internal function for pulling metadata, used in toArray and fromArray
     *
     * @param object $source_entity
     * @return array
     */
    protected function _getMetadata($source_entity)
    {
        $class = get_class($source_entity);

        $meta_result = [];
        $meta_result['em'] = $this->_em;
        $meta_result['factory'] = $this->_em->getMetadataFactory();
        $meta_result['meta'] = $meta_result['factory']->getMetadataFor($class);
        $meta_result['mappings'] = [];

        if ($meta_result['meta']->associationMappings) {
            foreach ($meta_result['meta']->associationMappings as $mapping_name => $mapping_info) {
                $entity = $mapping_info['targetEntity'];

                if (isset($mapping_info['joinTable'])) {
                    $meta_result['mappings'][$mapping_info['fieldName']] = [
                        'type' => 'many',
                        'entity' => $entity,
                        'is_owning_side' => ($mapping_info['isOwningSide'] == 1),
                        'mappedBy' => $mapping_info['mappedBy'],
                    ];
                } else {
                    if (isset($mapping_info['joinColumns'])) {
                        foreach ($mapping_info['joinColumns'] as $col) {
                            $join_meta = $meta_result['factory']->getMetadataFor($entity);
                            $join_ids = $join_meta->getIdentifierFieldNames();

                            $col_name = $col['name'];
                            $col_name = (isset($meta_result['meta']->fieldNames[$col_name])) ? $meta_result['meta']->fieldNames[$col_name] : $col_name;

                            $meta_result['mappings'][$col_name] = [
                                'name' => $mapping_name,
                                'type' => 'one_id',
                                'entity' => $entity,
                                'ids' => $join_ids,
                            ];

                            $meta_result['mappings'][$mapping_name] = [
                                'name' => $col_name,
                                'type' => 'one_entity',
                                'entity' => $entity,
                                'ids' => $join_ids,
                            ];
                        }
                    }
                }
            }
        }

        return $meta_result;
    }

    protected function _get($entity, $key)
    {
        $method_name = $this->_getMethodName($key, 'get');

        return (method_exists($entity, $method_name))
            ? $entity->$method_name()
            : null;
    }

    protected function _set($entity, $key, $value)
    {
        $method_name = $this->_getMethodName($key, 'set');

        return (method_exists($entity, $method_name))
            ? $entity->$method_name($value)
            : null;
    }

    // Converts "getvar_name_blah" to "getVarNameBlah".
    protected function _getMethodName($var, $prefix = '')
    {
        return $prefix . str_replace(" ", "", ucwords(strtr($var, "_-", "  ")));
    }
}