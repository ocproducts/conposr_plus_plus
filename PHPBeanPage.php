<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

abstract class PHPBeanPage extends Templateable
{
    protected $globalise = true;

    protected $pageMessages = array();
    protected $pageErrors = array();

    public function __construct()
    {
        $this->populateFromEnvironment();
    }

    protected function populateFromEnvironment($propertiesToIgnore = array())
    {
        $request = $_GET + $_POST;
        foreach (array_keys($request) as $property) {
            if ((property_exists($this, $property)) && ($this->$property !== null) && (!array_key_exists($property, $propertiesToIgnore))) {
                switch (gettype($this->$property)) {
                    case 'boolean':
                        $val = either_param_integer($property, null);
                        $this->$property = ($val === null) ? null : ($val == 1);
                        break;

                    case 'integer':
                        $this->$property = either_param_integer($property, null);
                        break;

                    case 'double':
                        $val = either_param_string($property, null);
                        $this->$property = ($val === null) ? null : floatval($val);
                        break;

                    case 'string':
                        $val = either_param_string($property, null);
                        $this->$property = ($val === null) ? null : trim($val);
                        break;

                    case 'NULL':
                        // Can't handle this - which is why we need to initialise the properties to scalar values
                        break;

                    case 'object':
                        if ($this->$property instanceof DateTime) {
                            $val = either_param_string($property, null);
                            if ($val === null) {
                                $this->$property = null;
                            } else {
                                $this->$property = DateTimeUtil::dateTimeFromString($val);
                                if ($this->$property === null) {
                                    throw new DatabaseException('Unrecognised date format for ' . $val);
                                }
                            }
                        }
                        break;
                }
            }
        }
    }

    protected function render($title, $tpl, $vars = array())
    {
        $vars += $this->populateToTemplateParams();

        $middle = do_template($tpl, $vars);
        if ($this->globalise) {
            $out = globalise($title, $middle);
        } else {
            $out = $middle;
        }
        $out->evaluate_echo();
    }

    public function addPageMessage($message)
    {
        $this->pageMessages[] = $message;
    }

    public function addPageError($message)
    {
        $this->pageErrors[] = array('FIELD_NAME' => null, 'MESSAGE' => $message);
    }

    public function addFieldError($fieldName, $message)
    {
        $this->pageErrors[] = array('FIELD_NAME' => $fieldName, 'MESSAGE' => $message);
    }
}
