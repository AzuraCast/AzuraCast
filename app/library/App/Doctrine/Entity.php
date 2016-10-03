<?php
namespace App\Doctrine;

use Doctrine\ORM\EntityManager;

class Entity implements \ArrayAccess
{
    /**
     * Magic methods.
     */

    public function __get($key)
    {
        $method_name = $this->_getMethodName($key, 'get');

        if (method_exists($this, $method_name))
            return $this->$method_name();
        else
            return $this->_getVar($key);
    }

    public function __set($key, $value)
    {
        $method_name = $this->_getMethodName($key, 'set');

        if (method_exists($this, $method_name))
            return $this->$method_name($value);
        else
            return $this->_setVar($key, $value);
    }

    public function __call($method, $arguments)
    {
        if (substr($method, 0, 3) == "get") {
            $var = $this->_getVarName(substr($method, 3));
            return $this->_getVar($var);
        }
        else if (substr($method, 0, 3) == "set") {
            $var = $this->_getVarName(substr($method, 3));
            $this->_setVar($var, $arguments[0]);
            return $this;
        }
        return null;
    }

    protected function _getVar($var)
    {
        if (property_exists($this, $var))
            return $this->$var;
        else
            return NULL;
    }

    protected function _setVar($var, $value)
    {
        if (property_exists($this, $var))
            $this->$var = $value;

        return $this;
    }

    // Converts "varNameBlah" to "var_name_blah".
    protected function _getVarName($var)
    {
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $var));
    }

    // Converts "getvar_name_blah" to "getVarNameBlah".
    protected function _getMethodName($var, $prefix = '')
    {
        return $prefix.str_replace(" ", "", ucwords(strtr($var, "_-", "  ")));
    }

    /**
     * ArrayAccess implementation
     */

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetSet($key, $value)
    {
        $method_name = $this->_getMethodName($key, 'set');

        if (method_exists($this, $method_name))
            return $this->$method_name($value);
        else
            return $this->_setVar($key, $value);
    }

    public function offsetGet($key)
    {
        $method_name = $this->_getMethodName($key, 'get');
        if (method_exists($this, $method_name))
            return $this->$method_name();
        else
            return $this->_getVar($key);
    }

    public function offsetUnset($offset)
    {
        if (property_exists($this, $offset))
            unset($this->$offset);
    }

    /**
     * FromArray (A Doctrine 1 Classic)
     *
     * @param EntityManager $em
     * @param $source
     * @return $this
     */
    public function fromArray(EntityManager $em, $source)
    {
        $metadata = self::getMetadata($em);

        $meta = $metadata['meta'];
        $mappings = $metadata['mappings'];

        foreach((array)$source as $field => $value)
        {
            if (isset($mappings[$field]))
            {
                $mapping = $mappings[$field];

                switch($mapping['type'])
                {
                    case "one_id":
                        $entity_field = $mapping['name'];
                        $entity_id = $mapping['ids'][0];

                        if (empty($value))
                        {
                            $this->$field = NULL;
                            $this->$entity_field = NULL;
                        }
                        else if ($value != $this->$field)
                        {
                            $obj_class = $mapping['entity'];
                            $obj = $em->getRepository($obj_class)->find($value);

                            if ($obj instanceof $obj_class)
                            {
                                $this->$field = $obj->$entity_id;
                                $this->$entity_field = $obj;
                            }
                        }
                    break;

                    case "one_entity":
                        $entity_id = $mapping['ids'][0];
                        $id_field = $mapping['name'];

                        if (empty($value))
                        {
                            $this->$field = NULL;
                            $this->$id_field = NULL;
                        }
                        else if ($value->$entity_id != $this->$field)
                        {
                            $this->$field = $value;
                            $this->$id_field = $value->$entity_id;
                        }
                    break;

                    case "many":
                        $obj_class = $mapping['entity'];

                        if ($mapping['is_owning_side'])
                        {
                            $this->$field->clear();

                            if ($value)
                            {
                                foreach((array)$value as $field_id)
                                {
                                    if(($field_item = $em->getRepository($obj_class)->find((int)$field_id)) instanceof $obj_class)
                                    {
                                        $this->$field->add($field_item);
                                    }
                                }
                            }
                        }
                        else
                        {
                            $foreign_field = $mapping['mappedBy'];

                            if (count($this->$field) > 0)
                            {
                                foreach($this->$field as $record)
                                {
                                    $record->$foreign_field->removeElement($this);
                                    $em->persist($record);
                                }
                            }

                            foreach((array)$value as $field_id)
                            {
                                $record = $em->getRepository($obj_class)->find((int)$field_id);

                                if($record instanceof $obj_class)
                                {
                                    $record->$foreign_field->add($this);
                                    $em->persist($record);
                                }
                            }

                            $em->flush();
                        }
                    break;
                }
            }
            else
            {
                if (!isset($meta->fieldMappings[$field]))
                    $field_info = array();
                else
                    $field_info = $meta->fieldMappings[$field];

                switch($field_info['type'])
                {
                    case "datetime":
                    case "date":
                        if (!($value instanceof \DateTime))
                        {
                            if ($value)
                            {
                                if (!is_numeric($value))
                                    $value = strtotime($value.' UTC');

                                $value = \DateTime::createFromFormat(\DateTime::ISO8601, gmdate(\DateTime::ISO8601, (int)$value));
                            }
                            else
                            {
                                $value = NULL;
                            }
                        }
                    break;

                    case "string":
                        if ($field_info['length'] && strlen($value) > $field_info['length'])
                            $value = substr($value, 0, $field_info['length']);
                    break;

                    case "decimal":
                    case "float":
                        if ($value !== NULL && !is_float($value))
                            $value = (float)$value;
                    break;

                    case "integer":
                    case "smallint":
                    case "bigint":
                        if ($value !== NULL)
                            $value = (int)$value;
                    break;

                    case "boolean":
                        if ($value !== NULL)
                            $value = (bool)$value;
                    break;
                }

                $this->__set($field, $value);
            }
        }

        return $this;
    }

    /**
     * ToArray (A Doctrine 1 Classic)
     *
     * @param EntityManager $em
     * @param bool $deep Iterate through collections associated with this item.
     * @param bool $form_mode Return values in a format suitable for ZendForm setDefault function.
     * @return array
     */
    public function toArray(EntityManager $em, $deep = FALSE, $form_mode = FALSE)
    {
        $return_arr = array();

        $class_meta = $em->getClassMetadata(get_called_class());

        $reflect = new \ReflectionClass($this);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

        if ($props)
        {
            foreach($props as $property)
            {
                $property->setAccessible(true);
                $prop_name = $property->getName();
                $prop_val = $property->getValue($this);

                if (isset($class_meta->fieldMappings[$prop_name]))
                    $prop_info = $class_meta->fieldMappings[$prop_name];
                else
                    $prop_info = array();

                if (is_array($prop_val))
                {
                    $return_arr[$prop_name] = $prop_val;
                }
                else if (!is_object($prop_val))
                {
                    if ($prop_info['type'] == "array")
                        $return_arr[$prop_name] = (array)$prop_val;
                    else
                        $return_arr[$prop_name] = (string)$prop_val;
                }
                else if ($prop_val instanceof \DateTime)
                {
                    $return_arr[$prop_name] = $prop_val->getTimestamp();
                }
                else if ($deep)
                {
                    if ($prop_val instanceof \Doctrine\Common\Collections\Collection)
                    {
                        $return_val = array();
                        if (count($prop_val) > 0)
                        {
                            foreach($prop_val as $val_obj)
                            {
                                if ($form_mode)
                                {
                                    $obj_meta = $em->getClassMetadata(get_class($val_obj));
                                    $id_field = $obj_meta->identifier;

                                    if ($id_field && count($id_field) == 1)
                                        $return_val[] = $val_obj->{$id_field[0]};
                                }
                                else
                                {
                                    $return_val[] = $val_obj->toArray($em, FALSE);
                                }
                            }
                        }

                        $return_arr[$prop_name] = $return_val;
                    }
                    else
                    {
                        $return_arr[$prop_name] = $prop_val->toArray($em, FALSE);
                    }
                }
            }
        }

        return $return_arr;
    }

    /**
     * Internal function for pulling metadata, used in toArray and fromArray
     *
     * @param null $class
     * @return array
     */
    public static function getMetadata(EntityManager $em, $class = null)
    {
        if ($class === null)
            $class = get_called_class();

        $meta_result = array();
        $meta_result['em'] = $em;
        $meta_result['factory'] = $em->getMetadataFactory();
        $meta_result['meta'] = $meta_result['factory']->getMetadataFor($class);
        $meta_result['mappings'] = array();

        if ($meta_result['meta']->associationMappings)
        {
            foreach ($meta_result['meta']->associationMappings as $mapping_name => $mapping_info)
            {
                $entity = $mapping_info['targetEntity'];

                if (isset($mapping_info['joinTable']))
                {
                    $meta_result['mappings'][$mapping_info['fieldName']] = array(
                        'type'           => 'many',
                        'entity'         => $entity,
                        'is_owning_side' => ($mapping_info['isOwningSide'] == 1),
                        'mappedBy'       => $mapping_info['mappedBy'],
                    );
                }
                else
                {
                    if (isset($mapping_info['joinColumns']))
                    {
                        foreach ($mapping_info['joinColumns'] as $col)
                        {
                            $join_meta = $meta_result['factory']->getMetadataFor($entity);
                            $join_ids = $join_meta->getIdentifierFieldNames();

                            $col_name = $col['name'];
                            $col_name = (isset($meta_result['meta']->fieldNames[$col_name])) ? $meta_result['meta']->fieldNames[$col_name] : $col_name;

                            $meta_result['mappings'][$col_name] = array(
                                'name'   => $mapping_name,
                                'type'   => 'one_id',
                                'entity' => $entity,
                                'ids'    => $join_ids,
                            );

                            $meta_result['mappings'][$mapping_name] = array(
                                'name'   => $col_name,
                                'type'   => 'one_entity',
                                'entity' => $entity,
                                'ids'    => $join_ids,
                            );
                        }
                    }
                }
            }
        }

        return $meta_result;
    }
}