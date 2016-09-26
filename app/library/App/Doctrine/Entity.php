<?php
namespace App\Doctrine;

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
     * @param $source
     * @return $this
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function fromArray($source)
    {
        $metadata = self::getMetadata();
        $em = $metadata['em'];
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
                            $obj = $obj_class::find($value);

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
                                    if(($field_item = $obj_class::find((int)$field_id)) instanceof $obj_class)
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
                                if(($record = $obj_class::find((int)$field_id)) instanceof $obj_class)
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
     * @param $deep Iterate through collections associated with this item.
     * @param $form_mode Return values in a format suitable for ZendForm setDefault function.
     * @return array
     */
    
    public function toArray($deep = FALSE, $form_mode = FALSE)
    {
        $return_arr = array();

        $em = self::getEntityManager();
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
                                    $return_val[] = $val_obj->toArray(FALSE);
                                }
                            }
                        }

                        $return_arr[$prop_name] = $return_val;
                    }
                    else
                    {
                        $return_arr[$prop_name] = $prop_val->toArray(FALSE);
                    }
                }
            }
        }
        
        return $return_arr;
    }
    
    /* Save (A Docrine 1 Classic) */
    public function save()
    {
        $em = self::getEntityManager();
        $em->persist($this);
        $em->flush($this);
    }
    
    /* Delete (A Docrine 1 Classic) */
    public function delete($hard_delete = FALSE)
    {
        $em = self::getEntityManager();
        
        // Support for soft-deletion.
        if (!$hard_delete && property_exists($this, 'deleted_at'))
        {
            // Determine type of deleted field.
            $class_meta = $em->getClassMetadata(get_called_class());
            $deleted_at_type = $class_meta->fieldMappings['deleted_at']['type'];
            
            if ($deleted_at_type == "datetime")
                $this->deleted_at = new \DateTime('NOW');
            else
                $this->deleted_at = true;
            
            $this->save();
        }
        else
        {
            $em = self::getEntityManager();
            $em->remove($this);
            $em->flush();
        }
    }
    public function hardDelete()
    {
        return $this->delete(TRUE);
    }
    
    public function detach()
    {
        $em = self::getEntityManager();
        $em->detach($this);
        return $this;
    }
    
    public function merge()
    {
        $em = self::getEntityManager();
        $em->merge($this);
        return $this;
    }
    
    /**
     * Static functions
     */

    /**
     * Get an Entity Manager object from the Dependency Injector.
     *
     * @deprecated
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEntityManager()
    {
        $di = $GLOBALS['di'];
        return $di->get('em');
    }
    
    /* Fetch the global entity manager to get a repository class. */
    public static function getRepository()
    {
        $class = get_called_class();
        $em = self::getEntityManager();
        
        return $em->getRepository($class);
    }
    
    /* Fetch an array of the current entities. */
    public static function fetchAll()
    {
        $repo = self::getRepository();
        return $repo->findAll();
    }
    
    public static function fetchArray($cached = true, $order_by = NULL, $order_dir = 'ASC')
    {
        $class = get_called_class();
        $em = self::getEntityManager();

        $qb = $em->createQueryBuilder()
            ->select('e')
            ->from($class, 'e');
        
        if ($order_by)
            $qb->orderBy('e.'.str_replace('e.', '', $order_by), $order_dir);
        
        return $qb->getQuery()->getArrayResult();
    }
    
    /* Generic dropdown builder function (can be overridden for specialized use cases). */
    public static function fetchSelect($add_blank = FALSE, \Closure $display = NULL, $pk = 'id', $order_by = 'name')
    {
        $select = array();
        
        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== FALSE)
            $select[''] = ($add_blank === TRUE) ? 'Select...' : $add_blank;
        
        // Build query for records.
        $class = get_called_class();
        $em = self::getEntityManager();
        
        $qb = $em->createQueryBuilder()->from($class, 'e');
        
        if ($display === NULL)
            $qb->select('e.'.$pk)->addSelect('e.name')->orderBy('e.'.$order_by, 'ASC');
        else
            $qb->select('e')->orderBy('e.'.$order_by, 'ASC');
        
        $results = $qb->getQuery()->getArrayResult();
        
        // Assemble select values and, if necessary, call $display callback.
        foreach((array)$results as $result)
        {
            $key = $result[$pk];
            $value = ($display === NULL) ? $result['name'] : $display($result);
            $select[$key] = $value;
        }
        
        return $select;
    }

    /* Find a specific item by primary key. */
    public static function find($id)
    {
        $repo = self::getRepository();
        return $repo->find($id);
    }

    /* Reset auto-increment key (MySQL Only). */
    public static function resetAutoIncrement()
    {
        $em = self::getEntityManager();

        $table_name = $em->getClassMetadata(get_called_class())->getTableName();
        $conn = $em->getConnection();

        return $conn->query('ALTER TABLE '.$conn->quoteIdentifier($table_name).' AUTO_INCREMENT = 1');
    }

    public static function getMetadata($class = null)
    {
        if ($class === null)
            $class = get_called_class();

        $em = self::getEntityManager();

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