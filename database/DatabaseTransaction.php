<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class DatabaseTransaction
{
    static function start($db = null)
    {
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }

        $db->query('START TRANSACTION');
    }

    static function end($db = null)
    {
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }

        $db->query('COMMIT');
    }

    static function rollback($db = null)
    {
        if ($db === null) {
            $db = $GLOBALS['SITE_DB'];
        }

        $db->query('ROLLBACK');

        Logger::error('Database transaction was rolled back');
    }
}
