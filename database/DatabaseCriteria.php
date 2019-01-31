<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class DatabaseCriteria
{
    const EQ = 0;
    const LT = 1;
    const LTE = 2;
    const GT = 3;
    const GTE = 4;
    const LIKE = 5;
    const BETWEEN = 6;

    protected $criteria = array();

    public function addCriteria($type, $property, $value)
    {
        $this->criteria[] = array($type, $property, $value);
    }

    public function toSQL()
    {
        $sql = '';

        foreach ($this->criteria as $c) {
            if ($sql != '') {
                $sql .= ' AND ';
            }

            list($type, $property, $value) = $c;

            // Check data types provided are possible for what we're doing
            switch (gettype($value)) {
                case 'boolean':
                    if ($type != self::EQ) {
                        throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }
                    break;

                case 'integer':
                    switch ($type) {
                        case self::LIKE:
                        case self::BETWEEN:
                            throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }
                    break;

                case 'double':
                    switch ($type) {
                        case self::LIKE:
                        case self::BETWEEN:
                            throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }
                    break;

                case 'string':
                    if (($type != self::EQ) && ($type != self::LIKE)) {
                        throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }
                    break;

                case 'NULL':
                    if ($type != self::EQ) {
                        throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }
                    break;

                case 'array':
                    if ($type != self::BETWEEN) {
                        throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }
                    if (count($value) != 2) {
                        throw new CPPException('Expects 2 values for any BETWEEN operation');
                    }
                    if (is_float($value[0])) {
                        if (!is_float($value[1])) {
                            throw new CPPException('Invalid values for BETWEEN operation');
                        }
                    } elseif (is_integer($value[0])) {
                        if (!is_integer($value[1])) {
                            throw new CPPException('Invalid values for BETWEEN operation');
                        }
                    } elseif ((is_object($value[0])) && ($value[0] instanceof DateTime)) {
                        if ((!is_object($value[1])) || (!$value[1] instanceof DateTime)) {
                            throw new CPPException('Invalid values for BETWEEN operation');
                        }
                    } else {
                        throw new CPPException('Invalid values for BETWEEN operation');
                    }
                    break;

                case 'object':
                    switch ($type) {
                        case self::LIKE:
                        case self::BETWEEN:
                            throw new CPPException('Unsupported data type, ' . gettype($value) . ', for operation');
                    }

                    if ($value instanceof DateTime) {
                        break;
                    }

                    throw new CPPException('Unsupported data type, ' . gettype($value));

                default:
                    throw new CPPException('Unsupported data type, ' . gettype($value));
            }

            if (gettype($value) != 'array') {
                $_value = DatabaseEntity::serializeValue($value);
            } else {
                $_value = array(strval(DatabaseEntity::serializeValue($value[0])), strval(DatabaseEntity::serializeValue($value[1])));
            }

            $field = convert_camelcase_to_underscore($property);

            switch ($type) {
                case self::EQ:
                    if ($_value == 'NULL') {
                        $sql .= $field . ' IS NULL';
                    } else {
                        $sql .= $field . '=' . $_value;
                    }
                    break;

                case self::LT:
                    $sql .= $field . '<' . $_value;
                    break;

                case self::LTE:
                    $sql .= $field . '<=' . $_value;
                    break;

                case self::GT:
                    $sql .= $field . '>' . $_value;
                    break;

                case self::GTE:
                    $sql .= $field . '>=' . $_value;
                    break;

                case self::LIKE:
                    $sql .= $field . ' LIKE ' . $_value;
                    break;

                case self::BETWEEN:
                    $sql .= $field . ' BETWEEN ' . $_value[0] . ' AND ' . $_value[1];
                    break;
            }
        }

        if ($sql == '') {
            $sql = '1';
        }

        return $sql;
    }
}
