<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

abstract class Templateable
{
    public function populateToTemplateParams()
    {
        $vars = array();

        $_vars = get_object_vars($this);
        foreach ($_vars as $key => $value) {
            $tplKey = strtoupper(convert_camelcase_to_underscore($key));
            $this->templatifyValue($vars, $tplKey, $value);
        }

        return $vars;
    }

    protected function templatifyValue(&$vars, $tplKey, $value)
    {
        $_value = null;

        switch (gettype($value)) {
            case 'NULL':
                $_value = '';
                $vars[$tplKey . '__HTML5'] = ''; // In case it is canonically a DateTime
                break;

            case 'array':
                $_value = array();
                if (count($value) > 0) {
                    $tmpVars = array();
                    foreach ($value as $key => $__value) {
                        $this->templatifyValue($_value, $this->templatifyValue($tmpVars, '', $key), $__value); // If it cannot be templated we'll get NULL out: that's fine
                    }
                }
                break;

            case 'boolean':
                $_value = $value ? '1' : '0';
                break;

            case 'integer':
                $_value = strval($value);
                break;

            case 'double':
                $_value = float_format($value, 20, true);
                break;

            case 'string':
                $_value = $value;
                break;

            case 'object':
                if ($value instanceof Tempcode) {
                    $_value = $value;

                    break;
                }

                if ($value instanceof DateTime) {
                    $_value = DateTimeUtil::formatDate($value);

                    $vars[$tplKey . '__HTML5'] =  DateTimeUtil::formatHTML5Date($value);

                    break;
                }

                if ($value instanceof Templateable) {
                    $_value = $value->populateToTemplateParams(); // Will produce an array (i.e. a Templateable object comes out much like a map-array would)
                }

                break;
        }

        $vars[$tplKey] = $_value;

        return $_value;
    }
}
