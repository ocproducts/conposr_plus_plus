<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class DatabaseEntityManager
{
    static public function getInstance()
    {
        static $instance;
        if ($instance === null) {
            $instance = new DatabaseEntityManager();
        }

        return $instance;
    }

    // ---

    protected $entityCache = array();

    // ---

    public function updateCache($entityType, $entityId, $entity)
    {
        $sz = serialize(array($entityType, $entityId));
        if ($entity === null) {
            unset($this->entityCache[$sz]);
        } else {
            $this->entityCache[$sz] = $entity;
        }
    }

    static public function getEntity($entityType, $entityId, $required = true, $db = null) /*ViaKey*/
    {
        $instance = DatabaseEntityManager::getInstance();

        if ($entityId === null) {
            if ($required) {
                throw new CPPException('Cannot look up null ' . $entityType . ' entity');
            }

            return null;
        }

        // Check cache
        $sz = serialize(array($entityType, $entityId));
        if (array_key_exists($sz, $instance->entityCache)) {
            return $instance->entityCache[$sz];
        }

        // Prep entity
        $entity = new $entityType();
        list(, , $keyProperties, , ) = $entity->getTableProperties();
        if (count($keyProperties) != 1) {
            throw new CPPException('Cannot do getEntity on ' . $entityType . ', it has no singular key');
        }

        // DB query
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }
        $table = convert_camelcase_to_underscore($entityType);
        $keyField = convert_camelcase_to_underscore($keyProperties[0]);
        $whereMap = array($keyField => DatabaseEntity::serializeValueLite($entityId));
        $rows = $db->query_select($table, array('*'), $whereMap, '', 1);
        if (!array_key_exists(0, $rows)) {
            if ($required) {
                throw new CPPException('Could not find ' . $entityType . ' entity #' . $entityId);
            }

            return null;
        }

        // Convert into entity
        $entity->populateFromRow($rows[0]);

        // Insert into cache
        $instance->entityCache[$sz] = $entity;

        return $entity;
    }

    static public function getEntityViaQuery($entityType, $sqlQuery, $parameters = array(), $required = true, $db = null)
    {
        $instance = DatabaseEntityManager::getInstance();

        // DB query
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }
        $table = convert_camelcase_to_underscore($entityType);
        $rows = $db->query_parameterised('SELECT * FROM ' . $table . ' ' . $sqlQuery, $parameters, 1);
        if (!array_key_exists(0, $rows)) {
            if ($required) {
                throw new CPPException('Cannot look up null ' . $entityType . ' entity');
            }

            return null;
        }

        // Convert into entity
        $entity = new $entityType();
        $entity->populateFromRow($rows[0]);

        // Insert into cache
        list(, , $keyProperties, , ) = $entity->getTableProperties();
        if (count($keyProperties) == 1) {
            $sz = serialize(array($entityType, $rows[0][convert_camelcase_to_underscore($keyProperties[0])]));
            $instance->entityCache[$sz] = $entity;
        }

        return $entity;
    }

    static public function getEntitiesViaQuery($entityType, $sqlQuery = '', $parameters = array(), $max = null, $start = 0, $db = null)
    {
        $instance = DatabaseEntityManager::getInstance();

        // DB query
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }
        $table = convert_camelcase_to_underscore($entityType);
        $rows = $db->query_parameterised('SELECT * FROM ' . $table . ' ' . $sqlQuery, $parameters, $max, $start);

        $entities = array();

        foreach ($rows as $row) {
            // Convert into entity
            $entity = new $entityType();
            $entity->populateFromRow($row);

            // Insert into cache
            list(, , $keyProperties, , ) = $entity->getTableProperties();
            if (count($keyProperties) == 1) {
                $sz = serialize(array($entityType, $row[convert_camelcase_to_underscore($keyProperties[0])]));
                $instance->entityCache[$sz] = $entity;
            }

            $entities[] = $entity;
        }

        return $entities;
    }

    static public function countEntitiesViaQuery($entityType, $sqlQuery = '', $parameters = array(), $db = null)
    {
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }
        $table = convert_camelcase_to_underscore($entityType);
        $rows = $db->query_parameterised('SELECT COUNT(*) AS cnt FROM ' . $table . ' ' . $sqlQuery, $parameters, 1);
        return $rows[0]['cnt'];
    }
}
