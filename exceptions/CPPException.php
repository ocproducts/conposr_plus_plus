<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class CPPException extends Exception
{
    public $field;

    public $is404 = false;
    public $isUserFriendlyWarning = false;
    public $isInformational = false;
}
