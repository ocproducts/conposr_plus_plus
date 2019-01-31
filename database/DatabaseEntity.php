<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

abstract class DatabaseEntity extends Templateable
{
    public $isNew = true;

    public function __call($method, $params)
    {
        $var = lcfirst(substr($method, 3));

        $isGet = (strncasecmp($method, 'get', 3) === 0);
        $isSet = (strncasecmp($method, 'set', 3) === 0);

        if (($isGet) || (($isGet) || ($isSet))) {
            if (!property_exists($this, $var)) {
                throw new DatabaseException('No such property: ' . $var);
            }
        }

        if ($isGet) {
            return $this->$var;
        }
        if ($isSet) {
            $type = gettype($this->$var);
            if ((is_array($type)) || ((is_object($type) && (!$type instanceof DateTime)))) {
                throw new DatabaseException('Cannot set on an entity\'s non-property: ' . $type);
            }

            $this->$var = $params[0];
            return null;
        }

        throw new DatabaseException('Missing method in ' . get_class($this) . ': ' . $method);
    }

    public function insertOrUpdate()
    {
        $entityProperties = $this->getEntityProperties();
        list($table, $tableProperties, $keyProperties, $autoIncrementProperty, $nonNullProperties) = $this->getTableProperties();
        $this->checkTypeConsistency($entityProperties, $tableProperties);

        $entityType = get_class($this);

        // Is it an insert?
        $whereMap = array();
        foreach ($keyProperties as $property) {
            $whereMap[convert_camelcase_to_underscore($property)] = self::serializeValueLite($this->$property);
        }
        $existingEntity = $GLOBALS['SITE_DB']->query_select($table, array('*'), $whereMap, '', 1);
        $isInsert = (count($existingEntity) == 0);

        // Find properties to save
        $propertyMap = array();
        foreach (array_keys($entityProperties) as $property) {
            if ($this->$property === null) {
                if (in_array($property, $nonNullProperties)) {
                    if (($isInsert) && ($property != $autoIncrementProperty)) {
                        // Error
                        throw new DatabaseException('Required property ' . $property . ' of entity ' . $entityType . ' is not set');
                    } else {
                        // Property skipped from update / property is auto increment ID
                    }
                } else {
                    // Property really is null
                    $propertyMap[$property] = null;
                }
            } else {
                // Property has regular value
                $propertyMap[$property] = $this->$property;
            }
        }

        if (count($propertyMap) == 0) {
            return null; // Nothing to actually update
        }

        // Run query
        if ($isInsert) {
            $insertMap = array();
            foreach ($propertyMap as $property => $value) {
                $insertMap[convert_camelcase_to_underscore($property)] = self::serializeValueLite($value);
            }

            $result = $GLOBALS['SITE_DB']->query_insert($table, $insertMap, true);
        } else {
            $updateMap = array();
            foreach ($propertyMap as $property => $value) {
                if (!in_array($property, $keyProperties)) {
                    $updateMap[convert_camelcase_to_underscore($property)] = self::serializeValueLite($value);
                }
            }

            $GLOBALS['SITE_DB']->query_update($table, $updateMap, $whereMap, '', 1);

            $result = null;
        }

        // Find out what to return / update caching
        if ($autoIncrementProperty === null) {
            $entityId = null;
        } else {
            if ($isInsert) {
                if ($result === null) {
                    throw new DatabaseException('Missing auto-increment result when inserting to ' . $entityType);
                }

                $this->$autoIncrementProperty = $result;
            }

            $entityId = $this->$autoIncrementProperty;

            $instance = DatabaseEntityManager::getInstance();
            $instance->updateCache($entityType, $entityId, $this);
        }

        return $entityId;
    }

    public function delete()
    {
        $entityProperties = $this->getEntityProperties();
        list($table, $tableProperties, $keyProperties, $autoIncrementProperty, ) = $this->getTableProperties();
        $this->checkTypeConsistency($entityProperties, $tableProperties);

        $whereMap = array();
        foreach ($keyProperties as $property) {
            $whereMap[convert_camelcase_to_underscore($property)] = self::serializeValueLite($this->$property);
        }
        $rowsAffected = $GLOBALS['SITE_DB']->query_delete($table, $whereMap, '', 1);

        if ($rowsAffected == 0) {
            throw new DatabaseException('Tried to delete a record that did not exist');
        }

        if ($autoIncrementProperty !== null) {
            $entityType = get_class($this);
            $instance = DatabaseEntityManager::getInstance();
            $instance->updateCache($entityType, $this->$autoIncrementProperty, null);
        }
    }

    public function getTableProperties()
    {
        $entityType = get_class($this);
        $table = convert_camelcase_to_underscore($entityType);

        static $cache = array();
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        $fields = $GLOBALS['SITE_DB']->query('SHOW FIELDS FROM ' . $table);

        $autoIncrementProperty = null;

        $tableProperties = array();
        $keyProperties = array();
        $nonNullProperties = array();
        foreach ($fields as $field) {
            $key = $field['Field'];
            $property = convert_underscore_to_camelcase($key);
            if (convert_camelcase_to_underscore($property) != $key) {
                throw new DatabaseException('Invalid field name, ' . $table . '.' . $key . ' (converts to ' . convert_camelcase_to_underscore($property) . ')');
            }

            $_type = strtolower($field['Type']);
            $type = null;
            if (strpos($_type, 'int') !== false) {
                $type = 'integer';
            } elseif (strpos($_type, 'bit') !== false) {
                $type = 'integer';
            } elseif (strpos($_type, 'decimal') !== false) {
                $type = 'float';
            } elseif (strpos($_type, 'numeric') !== false) {
                $type = 'float';
            } elseif (strpos($_type, 'float') !== false) {
                $type = 'float';
            } elseif (strpos($_type, 'double') !== false) {
                $type = 'float';
            } elseif (strpos($_type, 'char') !== false) {
                $type = 'string';
            } elseif (strpos($_type, 'text') !== false) {
                $type = 'string';
            } elseif (strpos($_type, 'binary') !== false) {
                $type = 'string';
            } elseif (strpos($_type, 'blob') !== false) {
                $type = 'string';
            } elseif (strpos($_type, 'date') !== false) {
                $type = 'DateTime';
            } elseif (strpos($_type, 'timestamp') !== false) {
                $type = 'DateTime';
            } else {
                throw new DatabaseException('Unrecognised data type, ' . $type . ' on ' . $table . '.' . $key);
            }

            $tableProperties[$property] = $type;

            if (strpos($field['Extra'], 'auto_increment') !== false) {
                $autoIncrementProperty = $property;
            }

            if (strpos($field['Key'], 'PRI') !== false) {
                $keyProperties[] = $property;
            }

            if (strtoupper($field['Null']) == 'NO') {
                $nonNullProperties[] = $property;
            }
        }
        ksort($tableProperties);

        if ((count($keyProperties) == 0) && (substr($table, 0, 1) != 'v')) {
            throw new DatabaseException('No key fields detected on ' . $table);
        }

        $ret = array($table, $tableProperties, $keyProperties, $autoIncrementProperty, $nonNullProperties);
        $cache[$table] = $ret;

        return $ret;
    }

    public function getEntityProperties()
    {
        $_propertiesInClass = array_diff(array_keys(get_object_vars($this)), array('isNew'));

        $entityProperties = array();
        foreach ($_propertiesInClass as $property) {
            $entityProperties[$property] = $this->getPHPDataType($this->$property);
        }

        ksort($entityProperties);

        return $entityProperties;
    }

    protected function checkTypeConsistency($a, $b)
    {
        $entityType = get_class($this);

        if ((count(array_intersect_key($a, $b)) != count($a)) || (count($a) != count($b))) {
            throw new DatabaseException('Key mismatch between database and entities on ' . $entityType . ', differences: ' . implode(',', array_diff(array_keys($a), array_keys($b))) . ' X ' . implode(',', array_diff(array_keys($b), array_keys($a))));
        }

        foreach ($a as $key => $type) {
            if ($type === null) {
                continue;
            }

            if ($b[$key] === null) {
                continue;
            }

            if ($type != $b[$key]) {
                throw new DatabaseException('Type mismatch between database and entities on ' . $entityType . 'x' . $key . ' (' . $type . ' vs ' . $b[$key] . ')');
            }
        }
    }

    protected function getPHPDataType($variable)
    {
        $type = gettype($variable);
        if ($type == 'NULL') {
            return null;
        }
        if ($type == 'object') {
            return get_class($variable);
        }
        return $type;
    }

    public function populateFromEnvironment($propertiesToIgnore = array())
    {
        $entityProperties = $this->getEntityProperties();
        list(, $tableProperties, , , ) = $this->getTableProperties();
        $this->checkTypeConsistency($entityProperties, $tableProperties);

        $request = $_GET + $_POST;
        foreach (array_keys($request) as $key) {
            $property = convert_underscore_to_camelcase($key);

            if ((array_key_exists($property, $entityProperties)) && (array_key_exists($property, $tableProperties)) && (!array_key_exists($property, $propertiesToIgnore))) {
                switch ($tableProperties[$property]) {
                    case 'integer':
                        $this->$property = either_param_integer($property, null);
                        break;

                    case 'string':
                        $val = either_param_string($property, null);
                        $this->$property = ($val === null) ? null : trim($val);
                        break;

                    case 'double':
                        $val = either_param_string($property, null);
                        $this->$property = ($val === null) ? null : floatval($val);
                        break;

                    case 'DateTime':
                        $val = either_param_string($property, null);
                        if ($val !== null) {
                            $this->$property = DateTimeUtil::dateTimeFromString($val);
                            if ($this->$property === null) {
                                throw new DatabaseException('Unrecognised date format for ' . $val);
                            }
                        } else {
                            $this->$property = null;
                        }
                        break;

                    default:
                        throw new DatabaseException('Unrecognised property type, ' . $tableProperties[$property]);
                }
            }
        }

        foreach (array_keys($_FILES) as $key) {
            $property = convert_underscore_to_camelcase($key);

            if ((is_uploaded_file($_FILES[$key]['tmp_name'])) && (property_exists($this, $property))) {
                ini_set('memory_limit', '-1');
                $this->$property = file_get_contents($_FILES[$key]['tmp_name']);
            }
        }
    }

    public function populateFromRow($row)
    {
        $this->isNew = false;

        $entityProperties = $this->getEntityProperties();
        list(, $tableProperties, , , ) = $this->getTableProperties();
        $this->checkTypeConsistency($entityProperties, $tableProperties);

        $rowProperties = array();
        foreach ($row as $key => $val) {
            $property = convert_underscore_to_camelcase($key);
            $rowProperties[$property] = $this->getPHPDataType($val);

            if ((array_key_exists($property, $entityProperties)) && (array_key_exists($property, $tableProperties))) {
                switch ($tableProperties[$property]) {
                    case 'integer':
                    case 'string':
                    case 'double':
                        $this->$property = $val;
                        break;

                    case 'DateTime':
                        $this->$property = DateTimeUtil::dateTimeFromString($val);
                        break;

                    default:
                        throw new DatabaseException('Unrecognised property type, ' . $tableProperties[$property]);
                }
            }
        }
        asort($rowProperties);

        $this->checkTypeConsistency($entityProperties, $rowProperties);
    }

    public function populateToRow()
    {
        $entityProperties = $this->getEntityProperties();
        list(, $tableProperties, , , ) = $this->getTableProperties();
        $this->checkTypeConsistency($entityProperties, $tableProperties);

        $row = array();

        $entityProperties = $this->getEntityProperties();
        foreach ($entityProperties as $property) {
            $key = convert_camelcase_to_underscore($property);

            switch ($tableProperties[$property]) {
                case 'integer':
                case 'string':
                case 'double':
                    $row[$key] = $this->$property;
                    break;

                case 'DateTime':
                    $row[$key] = DateTimeUtil::formatMySQLDateTime($this->$property);
                    break;

                default:
                    throw new DatabaseException('Unrecognised property type, ' . $tableProperties[$property]);
            }
        }

        return $row;
    }

    static function serializeValue($value)
    {
        $_value = null;

        switch (gettype($value)) {
            case 'boolean':
                $_value = $value ? '1' : '0';
                break;

            case 'integer':
                $_value = strval($value);
                break;

            case 'double':
                $_value = float_to_raw_string($value);
                break;

            case 'string':
                $_value = '\'' . db_escape_string($value) . '\'';
                break;

            case 'NULL':
                $_value = 'NULL';
                break;

            case 'object':
                if ($value instanceof DateTime) {
                    $_value = '\'' . db_escape_string(DateTimeUtil::formatMySQLDateTime($value)) . '\'';

                    break;
                }

                throw new CPPException('Unsupported data type, ' . gettype($value));

            default:
                throw new CPPException('Unsupported data type, ' . gettype($value));
        }

        return $_value;
    }

    // For when values will be fed through the database API of Conposr, so need less prep
    static function serializeValueLite($value)
    {
        $_value = null;

        switch (gettype($value)) {
            case 'boolean':
                $_value = $value ? 1 : 0;
                break;

            case 'integer':
            case 'double':
            case 'string':
                // Native support in Conposr
                $_value = $value;
                break;

            case 'NULL':
                $_value = null;
                break;

            case 'object':
                if ($value instanceof DateTime) {
                    $_value = DateTimeUtil::formatMySQLDateTime($value);

                    break;
                }

                throw new CPPException('Unsupported data type, ' . gettype($value));

            default:
                throw new CPPException('Unsupported data type, ' . gettype($value));
        }

        return $_value;
    }
}
