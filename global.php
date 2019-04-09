<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

spl_autoload_register('_conposr_plus_plus_autoloader');

require_once(__DIR__ . '/../conposr/global.php');

conposr_plus_plus_init();

function conposr_plus_plus_init()
{
    require_code('failure');
    set_throw_errors(true);

    require_css('global');
    require_javascript('global');
}

function _conposr_plus_plus_autoloader($class)
{
    $dir = __conposr_plus_plus_autoloader($class);
    $path = get_file_base() . '/lib/' . $dir . (($dir == '') ? '' : '/') . $class . '.php';
    if (!is_file($path)) {
        return; // May happen for 'class_exists' calls, or another autoloader may handle
        //fatal_exit('Could not find ' . $path);
    }
    require_once($path);
}

function __conposr_plus_plus_autoloader($class)
{
    $class = strtolower($class); // Class names are case insensitive in PHP

    if (in_array($class, array(
        'logger',
        'phpbeanpage',
        'templateable',
        'paginationpage',
    ))) {
        return 'conposr_plus_plus';
    }

    if (preg_match('#^.*exception$#', $class) != 0) {
        return 'conposr_plus_plus/exceptions';
    }

    if (preg_match('#^database.*$#', $class) != 0) {
        return 'conposr_plus_plus/database';
    }

    if (preg_match('#^.*util$#', $class) != 0) {
        return 'conposr_plus_plus/util';
    }

    return 'database_entities';
}

function check_post_request($fieldsToRequire = array(), $pageObject = null)
{
    if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] != 'POST')) {
        throw new CPPException('Missing form data');
    }

    $errors = false;

    foreach ($fieldsToRequire as $field) {
        $val = post_param_string($field, '');
        if ($val == '' || $val == '0') {
            $msg = post_param_string('label_for__' . $field, $field) . ' is required';
            if ($pageObject === null) {
                throw new IncorrectParameterException($msg);
            } else {
                $pageObject->addFieldError($field, $msg);
            }

            $errors = true;
        }
    }

    return !$errors;
}

function output_cpp_error($ex)
{
    set_throw_errors(false);

    if ($ex instanceof CPPException) {
        if ($ex->is404) {
            Logger::error('Error occurred; ' . $ex->getMessage() . "\n" . $ex->getTraceAsString());

            $page = new E404Page();
            $page->run();

            return;
        }

        if ($ex->isUserFriendlyWarning) {
            warn_exit(protect_from_escaping($ex->getMessage()));
        }
    }

    // Okay, fatal exit then...

    Logger::error('Error occurred; ' . $ex->getMessage() . "\n" . $ex->getTraceAsString());

    fatal_exit(protect_from_escaping($ex->getMessage()), $ex->getTrace());
}

class E404Page extends PHPBeanPage
{
    protected $globalise = false;

    public function run()
    {
        http_response_code(404);

        $title = 'Page not found';
        $tpl = '404';
        return $this->render($title, $tpl);
    }
}
